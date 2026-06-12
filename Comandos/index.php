<?php
/**
 * Punto de entrada para la carga de todos los comandos del sistema.
 *
 * Este archivo se encarga de incluir (require_once) cada archivo de comando
 * para que la llamada a Controlador::encolar_comando() que contiene cada uno
 * se ejecute, poblando la lista de registro pendiente.
 *
 * Al finalizar la inclusión de todos los comandos, el método
 * Controlador::cargar_comandos_pendientes() (invocado desde la inicialización
 * del sistema) los registrará automáticamente.
 *
 * @package Iteradores\Comandos
 * @since 1.3.1
 * @version 1.3.2
 */

// Comandos de depuración
require_once __DIR__.'/Depuracion/imprimir.php';
require_once __DIR__.'/Depuracion/limpiar.php';
require_once __DIR__.'/Depuracion/recoleccion.php';
// require_once __DIR__ . '/Depuracion/Limpiar.php';   // futuro
// Comanados de prueba
require_once __DIR__.'/Prueba/CrearNodo.php';
// Comandos de nodos
// require_once __DIR__ . '/Nodos/Contar.php';

// Comandos de persistencia
// require_once __DIR__ . '/Superestructura/Guardar.php';