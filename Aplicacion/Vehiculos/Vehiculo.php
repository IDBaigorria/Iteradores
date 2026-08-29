<?php
/**
 * Gestión de vehículos de empresas.
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
 * Lista los vehículos asociados a una empresa.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @return array Lista de vehículos con su información.
 */
function listar_vehiculos_de_empresa(string $nombre_empresa): array {
    // Buscar la empresa en todos los dueños? Necesitamos una forma de localizarla.
    // Asumimos que el nombre_empresa es único globalmente, o lo buscamos iterando.
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) return [];

        $adyacentes = $nodo_vehiculos->adyacentes();
        if (!$adyacentes) return [];

        $vehiculos = [];
        foreach ($adyacentes as $nombre_vehiculo => $nodo_vehiculo) {
            $nodo_nombre = $nodo_vehiculo->adyacente('nombre');
            $nodo_asientos = $nodo_vehiculo->adyacente('asientos');
            $vehiculos[] = [
                'nombre_vehiculo' => $nombre_vehiculo,
                'nombre' => $nodo_nombre ? $nodo_nombre->dato() : $nombre_vehiculo,
                'asientos' => $nodo_asientos ? $nodo_asientos->dato() : '0',
            ];
        }
        return $vehiculos;
    }
    return [];
}

/**
 * Agrega un nuevo vehículo a una empresa.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_vehiculo Nombre identificador del vehículo.
 * @param string $nombre_real Nombre visible del vehículo.
 * @param int $asientos Cantidad de asientos.
 * @return array Resultado de la operación.
 */
function agregar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_real = '', int $asientos = 44): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'El nombre del vehículo es obligatorio'];
    }

    // Buscar la empresa (global)
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) {
            $nodo_vehiculos = Nodo::crear_con_dato('');
            $nodo_empresa->_adyacente_en($nodo_vehiculos, 'vehiculos');
        }

        if ($nodo_vehiculos->adyacente($nombre_vehiculo)) {
            return ['exito' => false, 'error' => 'Ya existe un vehículo con ese nombre'];
        }

        $nodo_vehiculo = Nodo::crear_con_dato($nombre_vehiculo);
        $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_vehiculo), 'nombre');
        $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), 'asientos');

        $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);

        Controlador::guardar(Conf::NOMBRE_APP);

        return ['exito' => true];
    }
    return ['exito' => false, 'error' => 'Empresa no encontrada'];
}