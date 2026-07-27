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
 * ## Cambios en v1.4.8
 * - Se añaden las nuevas clases de antenas (`AntenaComun`, `AntenaDeMarcado`,
 *   `AntenaTraduccion`) y la `Senal` actualizada.
 * - Se eliminan `MapeoBytesMatrices` y `AplanadorSenal` (absorbidos por las
 *   antenas de traducción).
 * - `Talamo` y `ProcesadorDeDominio` se mantienen temporalmente hasta su
 *   refactorización completa.
 *
 * ## Orden de carga y justificación
 *
 * 1. **Nodos y utilidades base**
 *    `Nodo.php`, `NodoElectrico.php` y `miscelaneas/benchmark.php`
 *    proporcionan las clases fundamentales sin depender de comandos
 *    ni del controlador.
 *
 * 2. **Comunicación (Señal y Antenas)**
 *    Las nuevas clases de comunicación se cargan después de los nodos
 *    porque dependen de `Matriz2x2`, `NodoNumerico`, etc.
 *
 * 3. **Comandos y Comunicadores**
 *    `Comandos/index.php` y `Comunicadores/index.php` incluyen todas
 *    las definiciones de comandos y comunicadores. Cada archivo
 *    individual ejecuta al final `RegistroGlobal::encolar_comando()`
 *    o `RegistroGlobal::encolar_comunicador()` para autoencolarse.
 *    Como no importan directamente al Controlador, no disparan su
 *    inicialización prematura.
 *
 * 4. **Controlador**
 *    `Controlador/Controlador.php` define la clase Controlador y,
 *    al ser incluido, ejecuta `Controlador::inicializar()`. En ese
 *    momento los pendientes ya están encolados y se procesan
 *    correctamente.
 *
 * 5. **Pruebas (solo desarrollo)**
 *    `pruebas/Prueba148.php` se incluye al final para disponer de
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
 * @version   1.4.8
 */

// --- Utilidades base ----------------------------------
include_once("miscelaneas/benchmark.php");
include_once("miscelaneas/generarUUID.php");

// --- Configuración ------------------------------------
include_once("configuracion/Configuracion.php");

// --- Núcleo -------------------------------------------
include_once("Nucleo/Objeto.php");

// --- Entorno (debe ir después de Objeto.php) ----------
include_once("Configuracion/Entorno.php");

// --- Nodos --------------------------------------------
include_once("Nodos/Nodo.php");
include_once("Nodos/NodoElectrico.php");
include_once("Nodos/Matriz2x2.php");
include_once("Nodos/NodoNumerico.php");
include_once("Nodos/NodoPrimo.php");
include_once("Nodos/NodoParalelo.php");

// --- V 1.4.8 – Comunicación: Señal y Antenas ---------
include_once("Iteradores/Senal.php");
include_once("Iteradores/AntenaComun.php");
include_once("Iteradores/AntenaDeMarcado.php");
include_once("Controlador/AntenaTraduccion.php");   // en namespace Controlador

// --- Iteradores ---------------------------------------
include_once("Controlador/ProcesadorDeDominio.php");
include_once("Controlador/Talamo.php");

// --- Tiempo -------------------------------------------
include_once("Tiempo/RelojAstronomico.php");

// --- Comandos y Comunicadores (autoencolación) ---------
include_once("Controlador/RegistroGlobal.php");
include_once("Comandos/index.php");
include_once("Comunicadores/index.php");

// --- Controlador (inicialización) ----------------------
include_once("Controlador/Controlador.php");

// --- Pruebas (solo desarrollo) -------------------------
include_once("pruebas/Prueba149.php");