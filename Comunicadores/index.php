<?php
/**
 * Punto de entrada para la carga de todos los comunicadores del sistema.
 *
 * Este archivo se encarga de incluir (require_once) cada archivo de comunicdor
 * para que la llamada a Controlador::encolar_counicador() que contiene cada uno
 * se ejecute, poblando la lista de registro pendiente.
 *
 * Al finalizar la inclusión de todos los comunicadores, el método
 * Controlador::cargar_comunicadores_pendientes() (invocado desde la inicialización
 * del sistema) los registrará automáticamente.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 * @version 1.3.4
 */

require_once __DIR__.'/Comunicador.php';
require_once __DIR__.'/Archivo.php';
require_once __DIR__.'/HTML.php';
require_once __DIR__.'/Consola.php';

//require_once __DIR__.'/Depuracion/limpiar.php';
//require_once __DIR__.'/Depuracion/recoleccion.php';
// require_once __DIR__ . '/Depuracion/Limpiar.php';   // futuro
// Comanados de prueba
//require_once __DIR__.'/Prueba/CrearNodo.php';
// Comandos de nodos
// require_once __DIR__ . '/Nodos/Contar.php';

// Comandos de persistencia
// require_once __DIR__ . '/Superestructura/Guardar.php';