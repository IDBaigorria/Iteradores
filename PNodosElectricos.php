<?php
/*
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
include("Controlador/Controlador.php");
include_once("Nodos/NodoElectrico.php");
//crear
$n1=NodoElectrico::crear();
$n2=NodoElectrico::crear_con_dato(",a,");
$n3=NodoElectrico::crear_con_id("nodo n3");
$n4=NodoElectrico::crear_con_dato_e_id("mami","ta");
$n12=NodoElectrico::nodo($n1);
$n5=NodoElectrico::nodo("n5");
$n1->_adyacente_en($n2, "enlace a n2");

Controlador::establecer_fase("mamua");
$n1->_adyacente_en($n2, "denuevo vo");
//ENERGIA
NodoElectrico::_ejecutar_cuando_agota_por_fase(function(){echo "</br>se agoto (funcion por defecto fase0)";});
NodoElectrico::_ejecutar_cuando_satura_por_fase(function(){echo "</br>se saturo (funcion por defecto0)";});
$n1->_energia(0);
Controlador::establecer_fase("mamua2");
$n1->_adyacente_en($n2, "denuevo vo2");
//ENERGIA
NodoElectrico::_ejecutar_cuando_agota_por_fase(function(){echo "</br>se agoo (funcion por defecto fase1)";});
$n1->_ejecutar_cuando_agota(function(){echo "mkkkakakanaana";});
NodoElectrico::_ejecutar_cuando_satura_por_fase(function(){echo "</br>se saturo (funcion por defecto1)";});
$n1->_ejecutar_cuando_satura(function(){echo "mkkkakakanaana222";});
$n1->_energia(257);

NodoElectrico::eliminar($n1);
unset($n1);
//imprimir
$n1=NodoElectrico::crear();
$n2=NodoElectrico::crear_con_dato(",a,");
$n3=NodoElectrico::crear_con_id("nodo n");
$n4=NodoElectrico::crear_con_dato_e_id("mami","ta1");
$n12=NodoElectrico::nodo($n1);
$n5=NodoElectrico::nodo("n5");
$n1->_adyacente_en($n2, "enlace a n2");
NodoElectrico::imprimir_superestructura();
echo "<h2>Cantidad de nodos: ".Nodo::cantidad_de_nodos()."</h2>";
NodoElectrico::imprimir_errores();
NodoElectrico::imprimir_alertas()*/
?>*