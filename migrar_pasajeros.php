<?php
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

use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;

// Inicializar persistencia y cargar datos existentes
Controlador::establecer_metodo('SQL');
$nombre_app = Conf::NOMBRE_APP;
if (Controlador::existe($nombre_app)) {
    Controlador::cargar($nombre_app);
} else {
    echo "No existe la aplicación guardada.</br>";
    exit;
}

// Ver nodo especial
$raiz_especial = Nodo::nodo_por_id('pasajeros');
echo "¿Nodo especial 'pasajeros' existe? " . ($raiz_especial ? "SÍ" : "NO") . "</br>";
if ($raiz_especial) {
    $total_especial = count((array)$raiz_especial->adyacentes());
    echo "Cantidad de pasajeros en nodo especial: $total_especial</br>";
} else {
    echo "No hay nodo especial, nada que migrar.</br>";
    exit;
}

// Ver dueño
$raiz_usuarios = Nodo::nodo_por_id('usuarios');
if (!$raiz_usuarios) {
    echo "No existe raíz 'usuarios'.</br>";
    exit;
}
$nodo_dueno = $raiz_usuarios->adyacente('Parroquia_del_Carmen');
echo "¿Dueño 'Parroquia_del_Carmen' existe? " . ($nodo_dueno ? "SÍ" : "NO") . "</br>";
if (!$nodo_dueno) {
    echo "No se encontró al dueño.</br>";
    exit;
}

// Obtener o crear contenedor de pasajeros del dueño
$contenedor = $nodo_dueno->adyacente('pasajeros');
if (!$contenedor) {
    echo "El dueño no tiene contenedor 'pasajeros', se creará.</br>";
    $contenedor = Nodo::crear_con_dato('');
    $nodo_dueno->_adyacente_en($contenedor, 'pasajeros');
} else {
    echo "El dueño ya tiene contenedor 'pasajeros'.</br>";
}

$contador = 0;
foreach ($raiz_especial->adyacentes() as $dni => $nodo_pasajero) {
    $dni = (string)$dni; // Convertir a string
    if (!$contenedor->adyacente($dni)) {
        $contenedor->_adyacente_en($nodo_pasajero, $dni);
        $contador++;
    }
}

// Eliminar enlaces del nodo especial
foreach ($raiz_especial->adyacentes() as $dni => $nodo_pasajero) {
    $raiz_especial->eliminar_adyacente((string)$dni);
}

// Eliminar nodo especial
Nodo::eliminar($raiz_especial);

// Guardar cambios en SQL y JSON
Controlador::guardar($nombre_app);
Controlador::establecer_metodo('JSON');
Controlador::guardar($nombre_app);

echo "Migración guardada.</br>";

// Recargar desde SQL para verificar persistencia
Controlador::establecer_metodo('SQL');
Controlador::cargar($nombre_app);

// Verificar después de recargar
$raiz_usuarios_recargado = Nodo::nodo_por_id('usuarios');
$nodo_dueno_recargado = $raiz_usuarios_recargado ? $raiz_usuarios_recargado->adyacente('Parroquia_del_Carmen') : null;
if ($nodo_dueno_recargado) {
    $contenedor_recargado = $nodo_dueno_recargado->adyacente('pasajeros');
    if ($contenedor_recargado) {
        $total_recargado = count((array)$contenedor_recargado->adyacentes());
        echo "Verificación: contenedor 'pasajeros' del dueño tiene $total_recargado pasajeros.</br>";
    } else {
        echo "Verificación: el dueño NO tiene contenedor 'pasajeros' después de recargar.</br>";
    }
} else {
    echo "Verificación: no se encontró al dueño después de recargar.</br>";
}

// Comprobar que el nodo especial ya no existe
$raiz_especial_recargado = Nodo::nodo_por_id('pasajeros');
echo "Verificación: nodo especial 'pasajeros' " . ($raiz_especial_recargado ? "AÚN EXISTE" : "fue eliminado correctamente") . ".</br>";

echo "Script finalizado.</br>";
//Nodo::imprimir_alertas();
Nodo::imprimir_errores();