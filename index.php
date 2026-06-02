<?php
include("Nodos/Nodo.php");
include("miscelaneas/benchmark.php");
include("Controlador/Controlador.php");
include_once("Nodos/NodoElectrico.php");
use Iteradores\Nodos\Nodo;
use Iteradores\Nodos\NodoElectrico;
include_once("pruebas/PNodosElectricos.php");
NodoElectrico::imprimir_alertas();
Nodo::imprimir_errores();