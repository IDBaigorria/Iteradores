<?php
session_start();
header("Cache-control: no-cache, must-revalidate");
/**
 * Punto de entrada principal del framework.
 *
 * Orquesta la carga de todos los módulos del sistema en un orden
 * específico que garantiza la ausencia de dependencias circulares
 * y permite que los componentes se autoencolen correctamente en
 * {@link \Iteradores\Controlador\RegistroGlobal} antes de que el
 * {@link \Iteradores\Controlador\Controlador} sea inicializado.
 *
 * ## Orden de carga y justificación
 *
 * 1. **Nodos y utilidades base**
 *    `Nodo.php`, `NodoElectrico.php` y `miscelaneas/benchmark.php`
 *    proporcionan las clases fundamentales sin depender de comandos
 *    ni del controlador.
 *
 * 2. **Comandos y Comunicadores**
 *    `Comandos/index.php` y `Comunicadores/index.php` incluyen todas
 *    las definiciones de comandos y comunicadores. Cada archivo
 *    individual ejecuta al final `RegistroGlobal::encolar_comando()`
 *    o `RegistroGlobal::encolar_comunicador()` para autoencolarse.
 *    Como no importan directamente al Controlador, no disparan su
 *    inicialización prematura.
 *
 * 3. **Controlador**
 *    `Controlador/Controlador.php` define la clase Controlador y,
 *    al ser incluido, ejecuta `Controlador::inicializar()`. En ese
 *    momento los pendientes ya están encolados y se procesan
 *    correctamente.
 *
 * 4. **Pruebas (solo desarrollo)**
 *    `pruebas/PComando.php` se incluye al final para disponer de
 *    herramientas de prueba que pueden interactuar con el sistema
 *    ya inicializado.
 *
 * ## Notas para desarrollo
 *
 * - Cualquier nuevo comando o comunicador debe seguir el patrón
 *   de autoencolación y ser incluido en su respectivo `index.php`.
 * - Para cargar módulos opcionales o plugins, se recomienda
 *   hacerlo después de `Controlador::inicializar()` para que
 *   {@link RegistroGlobal} ya tenga la referencia al Controlador.
 *
 * @package   Iteradores
 * @since     1.0.0
 * @version   1.3.4
 */

// ─── Nodos y utilidades base ───────────────────────────
//  miselaneas
include_once("miscelaneas/benchmark.php");
include_once("miscelaneas/generarUUID.php");
//  configuracion
include_once("configuracion/Configuracion.php");
//  nucleo
include_once("Nucleo/Objeto.php");
//  entorno
include_once("Configuracion/Entorno.php");// debe ir despues de Objeto.php
//  nodos
include_once("Nodos/Nodo.php");
include_once("Nodos/NodoElectrico.php");
// tiempo
include_once("Tiempo/RelojAstronomico.php");

// ─── Comandos y Comunicadores (autoencolación) ─────────
include_once("Controlador/RegistroGlobal.php");
include_once("Comandos/index.php");
include_once("Comunicadores/index.php");

// ─── Controlador (inicialización) ──────────────────────
include_once("Controlador/Controlador.php");

// ─── Pruebas (solo desarrollo) ─────────────────────────
include_once("pruebas/PruebaEntorno136.php");