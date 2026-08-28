<?php
session_start();
header("Cache-control: no-cache, must-revalidate");
use Iteradores\Controlador\Controlador;
/**
 * Punto de entrada principal del framework.
 *
 * Orquesta la carga de todos los módulos del sistema en un orden
 * específico que garantiza la ausencia de dependencias circulares
 * y permite que los componentes se autoencolen correctamente en
 * {@link \Iteradores\Controlador\RegistroGlobal} antes de que el
 * {@link \Iteradores\Controlador\Controlador} sea inicializado.
 *
 * ## Cambios en v1.5i.piloto
 * - Se añade el enrutador de peticiones POST/GET mediante los archivos
 *   `aplicacion_POST.php` y `aplicacion_GET.php`.
 * - Se incorpora la gestión de persistencia: se carga la estructura
 *   si existe o se crea una nueva.
 * - Se agrega la pestaña de administración con control de acceso.
 *
 * ## Orden de carga y justificación
 * (se mantiene igual que en versiones anteriores)
 *
 * @author Ignacio David Baigorria
 *
 * @package   Iteradores
 * @since     1.0.0
 * @version   1.5i.piloto
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

// --- Inicialización de la persistencia ----------------
// Establecer método de persistencia (puede ser 'JSON', 'SQL', etc.)
Controlador::establecer_metodo('JSON'); // o el que corresponda

// Nombre del almacenamiento (archivo o base de datos)
$nombre_app = 'sistema_viajes';

if (Controlador::existe($nombre_app)) {
    Controlador::cargar($nombre_app);
} else {
    // Si no existe, creamos la estructura inicial vacía
    Controlador::guardar($nombre_app);
}

// --- Pruebas (solo desarrollo) -------------------------
//include_once("Pruebas/pruebas_iterador_persistencia.php");

// --- Enrutador de peticiones ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/aplicacion_POST.php';
    exit;
}

// Si es GET (o cualquier otra), servimos la interfaz
readfile(__DIR__ . '/aplicacion_GET.php');
