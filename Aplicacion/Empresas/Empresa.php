<?php
/**
 * Gestión de empresas de dueños.
 *
 * @package   Iteradores
 * @since     1.5piloto.5
 * @version   1.5piloto.5
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Lista las empresas asociadas a un dueño.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @return array Lista de empresas con su información.
 */
function listar_empresas_de_dueno(string $nombre_dueno): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return [];

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) return [];

    $adyacentes = $nodo_empresas->adyacentes();
    if (!$adyacentes) return [];

    $empresas = [];
    foreach ($adyacentes as $nombre_empresa => $nodo_empresa) {
        $nodo_nombre = $nodo_empresa->adyacente('nombre');
        $empresas[] = [
            'nombre_empresa' => $nombre_empresa,
            'nombre' => $nodo_nombre ? $nodo_nombre->dato() : $nombre_empresa,
        ];
    }
    return $empresas;
}

/**
 * Agrega una nueva empresa para un dueño.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_real Nombre visible de la empresa.
 * @return array Resultado de la operación.
 */
function agregar_empresa(string $nombre_dueno, string $nombre_empresa, string $nombre_real = ''): array {
    $nombre_empresa = trim($nombre_empresa);
    if (empty($nombre_empresa)) {
        return ['exito' => false, 'error' => 'El nombre de la empresa es obligatorio'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    // Obtener o crear contenedor de empresas del dueño
    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) {
        $nodo_empresas = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_empresas, 'empresas');
    }

    // Verificar que no exista una empresa con ese nombre
    if ($nodo_empresas->adyacente($nombre_empresa)) {
        return ['exito' => false, 'error' => 'Ya existe una empresa con ese nombre'];
    }

    $nodo_empresa = Nodo::crear_con_dato($nombre_empresa);
    $nodo_empresa->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_empresa), 'nombre');
    // Crear contenedor de vehículos vacío
    $nodo_empresa->_adyacente_en(Nodo::crear_con_dato(''), 'vehiculos');

    $nodo_empresas->_adyacente_en($nodo_empresa, $nombre_empresa);

    Controlador::guardar(Conf::NOMBRE_APP);

    return ['exito' => true];
}