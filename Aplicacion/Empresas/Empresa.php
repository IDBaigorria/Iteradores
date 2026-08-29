<?php
/**
 * Gestión de empresas de dueños.
 *
 * @package   Iteradores
 * @since     1.5piloto.5
 * @version   1.5piloto.8
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

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) {
        $nodo_empresas = Nodo::crear_con_dato('');
        $nodo_dueno->_adyacente_en($nodo_empresas, 'empresas');
    }

    if ($nodo_empresas->adyacente($nombre_empresa)) {
        return ['exito' => false, 'error' => 'Ya existe una empresa con ese nombre'];
    }

    $nodo_empresa = Nodo::crear_con_dato($nombre_empresa);
    $nodo_empresa->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_empresa), 'nombre');
    $nodo_empresa->_adyacente_en(Nodo::crear_con_dato(''), 'vehiculos');

    $nodo_empresas->_adyacente_en($nodo_empresa, $nombre_empresa);

    Controlador::guardar(Conf::NOMBRE_APP);

    return ['exito' => true];
}

/**
 * Edita el nombre visible de una empresa.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @param string $nombre_empresa Identificador de la empresa.
 * @param string $nuevo_nombre Nuevo nombre visible.
 * @return array Resultado.
 */
function editar_empresa(string $nombre_dueno, string $nombre_empresa, string $nuevo_nombre): array {
    $nombre_empresa = trim($nombre_empresa);
    $nuevo_nombre = trim($nuevo_nombre);
    if (empty($nuevo_nombre)) {
        return ['exito' => false, 'error' => 'El nombre visible no puede estar vacío'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) return ['exito' => false, 'error' => 'El dueño no tiene empresas'];

    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
    if (!$nodo_empresa) return ['exito' => false, 'error' => 'Empresa no encontrada'];

    $nodo_nombre = $nodo_empresa->adyacente('nombre');
    if ($nodo_nombre) {
        $nodo_nombre->_dato($nuevo_nombre);
    } else {
        $nodo_empresa->_adyacente_en(Nodo::crear_con_dato($nuevo_nombre), 'nombre');
    }

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}

/**
 * Elimina una empresa y todos sus vehículos asociados.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @param string $nombre_empresa Identificador de la empresa.
 * @return array Resultado.
 */
function eliminar_empresa(string $nombre_dueno, string $nombre_empresa): array {
    $nombre_empresa = trim($nombre_empresa);
    if (empty($nombre_empresa)) {
        return ['exito' => false, 'error' => 'Nombre de empresa obligatorio'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    $nodo_dueno = $raiz_usuarios->adyacente($nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) return ['exito' => false, 'error' => 'El dueño no tiene empresas'];

    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
    if (!$nodo_empresa) return ['exito' => false, 'error' => 'Empresa no encontrada'];

    // Eliminar vehículos asociados (se eliminan enlaces, nodos quedan huérfanos)
    $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
    if ($nodo_vehiculos) {
        foreach ($nodo_vehiculos->adyacentes() as $nombre_vehiculo => $nodo_vehiculo) {
            // TODO: Aquí se deberían eliminar los nodos huérfanos (pisos, asientos, etc.)
            // Por ahora solo se elimina el enlace, dejando nodos inaccesibles.
            $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);
        }
    }

    // Eliminar enlace de la empresa
    $nodo_empresas->eliminar_adyacente($nombre_empresa);

    Controlador::guardar(Conf::NOMBRE_APP);
    return ['exito' => true];
}