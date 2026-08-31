<?php
session_start();
header("Cache-control: no-cache, must-revalidate");

use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;

/**
 * Punto de entrada principal del framework.
 *
 * Orquesta la carga de todos los módulos del sistema en un orden
 * específico que garantiza la ausencia de dependencias circulares
 * y permite que los componentes se autoencolen correctamente en
 * {@link \Iteradores\Controlador\RegistroGlobal} antes de que el
 * {@link \Iteradores\Controlador\Controlador} sea inicializado.
 *
 * @author Ignacio David Baigorria
 * @package   Iteradores
 * @since     1.0.0
 * @version   1.5piloto.3
 */

// --- Utilidades base ----------------------------------
include_once("miscelaneas/benchmark.php");
include_once("miscelaneas/generarUUID.php");

// --- Configuración ------------------------------------
include_once("Configuracion/Configuracion.php");

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
include_once("Controlador/AntenaTraduccion.php");

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

// Inicialización de la persistencia
Controlador::establecer_metodo('SQL');
$nombre_app = Conf::NOMBRE_APP;
if (Controlador::existe($nombre_app)) {
    Controlador::cargar($nombre_app);
} else {
    Controlador::guardar($nombre_app);
}

// Incluir módulos de la aplicación
require_once __DIR__ . '/Aplicacion/Usuarios/Usuario.php';
require_once __DIR__ . '/Aplicacion/Sesiones/Sesion.php';
require_once __DIR__ . '/Aplicacion/Admin/Admin.php';
require_once __DIR__ . '/Aplicacion/Autenticacion/Autenticacion.php';
require_once __DIR__ . '/Aplicacion/Empresas/Empresa.php';
require_once __DIR__ . '/Aplicacion/Vehiculos/Vehiculo.php';
require_once __DIR__ . '/Aplicacion/Viajes/Viaje.php';
require_once __DIR__ . '/Aplicacion/Ventas/Venta.php';
require_once __DIR__ . '/Aplicacion/Pasajeros/Pasajero.php';
require_once __DIR__ . '/Aplicacion/Enrutador.php';
// ==== Bloque temporal para pruebas de árbol ====
if (isset($_GET['probar_arbol'])) {
    require_once __DIR__ . '/miscelaneas/Arbol.php';
    require_once __DIR__ . '/miscelaneas/pruebas_arbol.php';
    exit;
}

// Crear usuario administrador si no existe
if (!buscar_usuario_por_codigo(Conf::CODIGO_ADMIN)) {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        Nodo::crear_con_id('usuarios');
        $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    }
    $nodo_admin = Nodo::crear_con_dato(Conf::NOMBRE_ADMIN);
    $nodo_admin->_adyacente_en(Nodo::crear_con_dato(Conf::NOMBRE_ADMIN), 'nombre_real');
    $nodo_admin->_adyacente_en(Nodo::crear_con_dato('admin'), 'nivel');
    $nodo_admin->_adyacente_en(Nodo::crear_con_dato(Conf::CODIGO_ADMIN), 'codigo_acceso');
    $raiz_usuarios->_adyacente_en($nodo_admin, Conf::NOMBRE_ADMIN);
    Controlador::guardar($nombre_app);
}

// Enrutar según método
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    enrutar_peticion_post($_POST['accion'], $_POST);
    Controlador::guardar($nombre_app);
    Controlador::establecer_metodo('JSON');
    Controlador::guardar($nombre_app);
    //Controlador::imprimir_alertas();
    //Controlador::imprimir_errores();
    exit;
}

// Si es GET, mostrar la interfaz
readfile(__DIR__ . '/aplicacion_GET.html');