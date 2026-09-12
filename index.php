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
// ==== Bloque temporal para migración de micros (versión 1.5piloto.27) ====
if (isset($_GET['migrar_micros'])) {
    require_once __DIR__ . '/miscelaneas/migrar_micros.php';
    header('Content-Type: text/plain; charset=utf-8');
    $res = migrar_micros_empresa();
    echo "Migración completada.\n";
    echo "Dueños procesados: {$res['duenos_procesados']}\n";
    echo "Micros procesados: {$res['micros_procesados']}\n";
    echo "Micros migrados:   {$res['micros_migrados']}\n";
    echo "Sin cambios:       {$res['micros_sin_cambio']}\n";
    echo "Sin empresa:       {$res['micros_sin_empresa']}\n";
    echo "Sin match:         {$res['micros_sin_match']}\n";
    exit;
}
// ==== Bloque temporal para migración de patente en micros (v1.5piloto.27) ====
if (isset($_GET['migrar_micros_patente'])) {
    require_once __DIR__ . '/miscelaneas/migrar_micros_patente.php';
    header('Content-Type: text/plain; charset=utf-8');
    $res = migrar_micros_patente();
    echo "Migración de patente completada.\n";
    echo "Micros procesados:  {$res['micros_procesados']}\n";
    echo "Micros limpiados:   {$res['micros_limpiados']}\n";
    echo "Sin enlace previo:  {$res['micros_sin_enlace']}\n";
    echo "Sin copia:          {$res['micros_sin_copia']}\n";
    exit;
}
// ==== Bloque temporal para migración de terminales autorizadas (v1.5piloto.31) ====
// Convierte los enlaces directos al Nodo Usuario terminal en nodos intermedios
// "TerminalViaje", que cuelgan del contenedor `terminales_autorizadas`.
// Es idempotente: si un enlace ya apunta a un TerminalViaje, lo saltea.
if (isset($_GET['migrar_terminales_autorizadas'])) {
    require_once __DIR__ . '/miscelaneas/migrar_terminales_autorizadas.php';
    header('Content-Type: text/plain; charset=utf-8');
    $res = migrar_terminales_autorizadas();
    echo "Migración de terminales autorizadas completada.\n";
    echo "Dueños procesados:         {$res['duenos_procesados']}\n";
    echo "Viajes procesados:         {$res['viajes_procesados']}\n";
    echo "Terminales migradas:       {$res['terminales_migradas']}\n";
    echo "Terminales sin cambio:     {$res['terminales_sin_cambio']}\n";
    echo "Terminales sin nodo usuario: {$res['terminales_sin_nodo_usuario']}\n";
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
    Controlador::establecer_metodo('JSON');
    Controlador::guardar($nombre_app);
}

// Manejo de impresión
if (isset($_GET['imprimir']) && $_GET['imprimir'] === '1') {
    require_once __DIR__ . '/Aplicacion/Impresion/Impresion.php';
    $dni_filtro = $_GET['dni'] ?? '';
    generar_impresion($_GET['tipo'] ?? '', $_GET['id_venta'] ?? '', $dni_filtro);
    exit;
}

// Enrutar según método
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    enrutar_peticion_post($_POST['accion'], $_POST);
    Controlador::guardar($nombre_app);
    Controlador::establecer_metodo('JSON');
    Controlador::guardar($nombre_app);
    Controlador::imprimir_alertas();
    Controlador::imprimir_errores();
    exit;
}

// Si es GET, mostrar la interfaz
readfile(__DIR__ . '/aplicacion_GET.html');