<?php
// ============================================================
// TEST DE ENTORNO E IMPRESIÓN (PHP)
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
    echo "⚠️ Las pruebas deberían ejecutarse en entorno DESARROLLO<br>\n";
}

echo "🚀 Inicio de pruebas para Entorno e Impresión (PHP)<br>\n";

// ──────────────────────────────────────────────────────────
// 0. CONFIGURACIÓN INICIAL
// ──────────────────────────────────────────────────────────
echo "\n🔹 Configuración inicial de Entorno<br>\n";
echo "▶ Modo actual: " . Entorno::modo() . "<br>\n";
echo "▶ Tipo de salida: " . Entorno::salida() . "<br>\n";
echo "▶ Persistencia: " . Entorno::persistencia() . "<br>\n";
echo "▶ ¿Permite pruebas?: " . (Entorno::permite_pruebas() ? "Sí" : "No") . "<br>\n";
/*
// ──────────────────────────────────────────────────────────
// 1. PRUEBAS DE ENTORNO
// ──────────────────────────────────────────────────────────
echo "\n🔹 Pruebas de Entorno<br>\n";

echo "▶ 1.1 Cambiar a producción y verificar<br>\n";
Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
echo "   Modo: " . Entorno::modo() . " (debe ser 'produccion')<br>\n";
echo "   ¿Es producción?: " . (Entorno::es_produccion() ? "Sí (correcto)" : "No (error)") . "<br>\n";
echo "   ¿Permite pruebas?: " . (Entorno::permite_pruebas() ? "Sí (error)" : "No (correcto)") . "<br>\n";

echo "▶ 1.2 Cambiar tipo de salida a consola y verificar<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
echo "   Tipo de salida: " . Entorno::salida() . " (debe ser 'consola')<br>\n";
echo "   ¿Es consola?: " . (Entorno::es_consola() ? "Sí (correcto)" : "No (error)") . "<br>\n";

echo "▶ 1.3 Cambiar método de persistencia a json<br>\n";
Entorno::establecer_persistencia(Entorno::PERSISTENCIA_JSON);
echo "   Persistencia: " . Entorno::persistencia() . " (debe ser 'json')<br>\n";
echo "   ¿Es json?: " . (Entorno::es_persistencia_json() ? "Sí (correcto)" : "No (error)") . "<br>\n";

echo "▶ 1.4 Volver a desarrollo, HTML y SQL<br>\n";
Entorno::establecer_modo(Entorno::MODO_DESARROLLO);
Entorno::establecer_salida(Entorno::SALIDA_HTML);
Entorno::establecer_persistencia(Entorno::PERSISTENCIA_SQL);
echo "   Modo: " . Entorno::modo() . "<br>\n";
echo "   Salida: " . Entorno::salida() . "<br>\n";
echo "   Persistencia: " . Entorno::persistencia() . "<br>\n";

// ──────────────────────────────────────────────────────────
// 2. PRUEBAS DE OBJETO (ERRORES Y ALERTAS)
// ──────────────────────────────────────────────────────────
echo "\n🔹 Pruebas de Objeto (errores y alertas)<br>\n";


// Generar algunos errores y alertas
NodoElectrico::_error("Error de prueba 1: algo salió mal");
NodoElectrico::_alerta("Alerta de prueba 1: precaución");

echo "▶ 2.1 Imprimir errores en modo HTML (debe verse un bloque HTML)<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_HTML);
NodoElectrico::imprimir_errores();

echo "▶ 2.2 Imprimir alertas en modo HTML<br>\n";
NodoElectrico::imprimir_alertas();

echo "▶ 2.3 Imprimir errores en modo CONSOLA<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
NodoElectrico::imprimir_errores();

echo "▶ 2.4 Imprimir alertas en modo CONSOLA<br>\n";
NodoElectrico::imprimir_alertas();

echo "▶ 2.5 Verificar que en producción los errores/alertas SÍ se muestran<br>\n";
Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
echo "   (Debe verse el error y alerta a continuación)<br>\n";
NodoElectrico::imprimir_errores();
NodoElectrico::imprimir_alertas();

// Volver a desarrollo para el resto de pruebas
Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

// ──────────────────────────────────────────────────────────
// 3. PRUEBAS DE IMPRESIÓN DE NODO (CLASE BASE)
// ──────────────────────────────────────────────────────────
echo "\n🔹 Pruebas de Nodo (imprimir)<br>\n";

$nodo_base = Nodo::crear_con_dato("Nodo Base");
$nodo_base->_adyacente_en(Nodo::crear_con_dato("Vecino"), "enlace1");

echo "▶ 3.1 Imprimir Nodo en modo CONSOLA<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
$nodo_base->imprimir();

echo "▶ 3.2 Imprimir Nodo en modo HTML<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_HTML);
$nodo_base->imprimir();

echo "▶ 3.3 Verificar que en producción NO se imprime y se genera alerta<br>\n";
Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
$nodo_base->imprimir();
echo "   (Debe aparecer una alerta de que no está permitido)<br>\n";
Nodo::imprimir_alertas();

Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

// ──────────────────────────────────────────────────────────
// 4. PRUEBAS DE IMPRESIÓN DE NODOELECTRICO
// ──────────────────────────────────────────────────────────
echo "\n🔹 Pruebas de NodoElectrico (imprimir)<br>\n";

$ne1 = NodoElectrico::crear_con_dato("NE1");
$ne2 = NodoElectrico::crear_con_dato("NE2");
$ne3 = NodoElectrico::crear_con_dato("NE3");

// Configurar adyacentes y pesos en la fase actual
$ne1->_adyacente_en($ne2, "e1");
$ne1->_peso("e1", 10);                     // default acumula
$ne1->_adyacente_en($ne3, "e2");
$ne1->_peso("e2", 5.5, "distancia", false); // asignación directa
$ne1->_peso("e2", 3, "coste");             // acumula coste

echo "▶ 4.1 Imprimir NodoElectrico en CONSOLA (fase actual: " . NodoElectrico::fase() . ")<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
$ne1->imprimir();

echo "▶ 4.2 Imprimir NodoElectrico en HTML<br>\n";
Entorno::establecer_salida(Entorno::SALIDA_HTML);
$ne1->imprimir();

echo "▶ 4.3 Verificar que en producción NO se imprime<br>\n";
Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
$ne1->imprimir();
echo "   (Debe aparecer una alerta)<br>\n";
NodoElectrico::imprimir_alertas();

Entorno::establecer_modo(Entorno::MODO_DESARROLLO);
Entorno::establecer_salida(Entorno::SALIDA_HTML);
*/
// ──────────────────────────────────────────────────────────
// 6. PRUEBAS DE IMPRESIÓN DE LA SUPERESTRUCTURA
// ──────────────────────────────────────────────────────────
echo "\n🔹 Impresión de la superestructura (Controlador)\n";

// Aseguramos que el token esté disponible ejecutando una prueba dummy
Controlador::ejecutar_prueba(function($token) {
    echo "▶ 6.1 Preparar superestructura mixta\n";
    // Limpiar superestructura previa (opcional, pero para control)
    // No tenemos un método para vaciarla completamente, así que trabajamos con lo existente.
    // Para la prueba, creamos nodos frescos de ambos tipos.

    // Nodos base
    $n1 = Nodo::crear_con_dato("Base 1");
    $n2 = Nodo::crear_con_dato("Base 2");
    $n1->_adyacente_en($n2, "b1");

    // Nodos eléctricos con pesos
    $ne1 = NodoElectrico::crear_con_dato("Eléctrico A");
    $ne2 = NodoElectrico::crear_con_dato("Eléctrico B");
    $ne1->_adyacente_en($ne2, "e1");
    $ne1->_peso("e1", 10);                     // default acumula
    $ne1->_peso("e2", 5.5, "distancia", false); // e2 aún no existe, error esperado
    // Creamos e2 primero
    $ne1->_adyacente_en($ne2, "e2");
    $ne1->_peso("e2", 5.5, "distancia", false);

    // Mezclamos: nodo base apunta a eléctrico
    $n1->_adyacente_en($ne1, "mixto");

    echo "   Nodos creados: 2 base + 2 eléctricos\n";

    echo "▶ 6.2 Imprimir superestructura en modo CONSOLA\n";
    Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
    Controlador::imprimir_superestructura();

    echo "▶ 6.3 Imprimir superestructura en modo HTML\n";
    Entorno::establecer_salida(Entorno::SALIDA_HTML);
    Controlador::imprimir_superestructura();

    echo "▶ 6.4 Verificar que en producción NO se imprime\n";
    Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
    Controlador::imprimir_superestructura();
    echo "   (Debe aparecer una alerta)\n";
    NodoElectrico::imprimir_alertas();
    Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

    echo "▶ 6.5 Superestructura vacía\n";
    // No podemos vaciarla sin un método, así que simulamos comprobando que hay nodos.
    // Si la superestructura no estuviera vacía, no mostraría alerta.
    echo "   (Actualmente hay " . Nodo::cantidad_de_nodos() . " nodos en la superestructura)\n";

    echo "✅ Pruebas de superestructura completadas\n";
});

// ──────────────────────────────────────────────────────────
// 6. RESUMEN FINAL
// ──────────────────────────────────────────────────────────
echo "\n✅ Pruebas de Entorno e Impresión finalizadas<br>\n";