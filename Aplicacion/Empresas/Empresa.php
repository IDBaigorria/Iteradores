<?php
/**
 * Gestión de empresas de dueños.
 *
 * @package   Iteradores
 * @since     1.5piloto.5
 * @version   1.5piloto.83n
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
include_once("./Aplicacion/Compartidos.php");
include_once("./Aplicacion/Vehiculos/Vehiculo.php");

/**
 * Lista las empresas asociadas a un dueño.
 *
 * @param string $nombre_dueno Nombre del dueño.
 * @return array Lista de empresas con su información.
 */
function listar_empresas_de_dueno(string $nombre_dueno, ?Nodo $nodo_contexto = null): array {
    // Fase B2.2.4 (v76u): contexto opcional. Si viene, navega
    // por ahí en lugar de resolver `usuarios → dueño`. Mismo
    // patrón que Viaje y Venta. Lo usa el terminal para ver
    // solo las empresas compartidas.
    if ($nodo_contexto === null) {
        // Fase B3.1: ID especial directo.
        $nodo_contexto = Nodo::nodo_por_id('us_' . $nombre_dueno);
    }
    if (!$nodo_contexto) return [];

    $nodo_empresas = $nodo_contexto->adyacente('empresas');
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

    // Fase B3.1: ID especial directo.
    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
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

    // Fix 1.5piloto.83n: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("agregar_empresa: guardar_ambos falló", 'error');
        Controlador::_error("agregar_empresa: guardar_ambos falló");
        return [
            'exito' => false,
            'error' => 'La empresa se creó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }

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

    // Fase B3.1: ID especial directo.
    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
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

    // Fix 1.5piloto.83n: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("actualizar_empresa: guardar_ambos falló", 'error');
        Controlador::_error("actualizar_empresa: guardar_ambos falló");
        return [
            'exito' => false,
            'error' => 'La actualización se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }
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

    // Fase B3.1: ID especial directo.
    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
    if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) return ['exito' => false, 'error' => 'El dueño no tiene empresas'];

    $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
    if (!$nodo_empresa) return ['exito' => false, 'error' => 'Empresa no encontrada'];

    // Fase 2, v74y: destruir cada vehículo completo (asientos,
    // pisos, listas circulares de asientos, campos), después
    // el contenedor `vehiculos`, y finalmente el nodo empresa
    // con sus campos. Antes solo se desenlazaban los enlaces,
    // dejando N × ~100 nodos huérfanos por empresa.
    $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
    if ($nodo_vehiculos) {
        $adyacentes_vehiculos = (array) $nodo_vehiculos->adyacentes();
        foreach ($adyacentes_vehiculos as $nombre_vehiculo => $nodo_vehiculo) {
            $nodo_vehiculos->eliminar_adyacente((string)$nombre_vehiculo);
            _destruir_vehiculo_completo($nodo_vehiculo);
        }
        $nodo_empresa->eliminar_adyacente('vehiculos');
        _destruir_campos_simples($nodo_vehiculos);
        Nodo::eliminar($nodo_vehiculos);
    }

    // Fase B2.4.4 (v77q): desincronizar la empresa de TODOS
    // los compartidos del dueño. Si no, los compartidos
    // quedan con un enlace entrante al nodo empresa y
    // Nodo::eliminar falla en silencio.
    _desincronizar_empresa_de_compartidos($nodo_dueno, $nombre_empresa);

    // Desenlazar la empresa del contenedor del dueño y destruirla.
    $nodo_empresas->eliminar_adyacente($nombre_empresa);
    _destruir_campos_simples($nodo_empresa);
    Nodo::eliminar($nodo_empresa);

    // Fix 1.5piloto.83n: chequear guardar_ambos.
    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("eliminar_empresa: guardar_ambos falló", 'error');
        Controlador::_error("eliminar_empresa: guardar_ambos falló");
        return [
            'exito' => false,
            'error' => 'La eliminación se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }
    return ['exito' => true];
}

/**
 * Elimina las empresas y vehículos "de prueba" de un dueño.
 *
 * Criterio por prefijo del identificador (clave del
 * contenedor):
 *   - Empresas: fotoauto, viajecambiomicro_emp_
 *   - Vehículos: fotoauto, viajecambiomicro_veh_
 *
 * Si una empresa matchea, se destruye entera (con sus
 * vehículos). Si no, se itera sus vehículos y se borran
 * los que matcheen.
 *
 * Si un vehículo está referenciado por un micro de un
 * viaje, eliminar_vehiculo falla y se reporta en
 * `salteados`.
 *
 * Disponible solo en modo pruebas.
 *
 * @param string $nombre_dueno
 * @return array
 * @since 1.5piloto.80a
 */
function limpiar_empresas_vehiculos_de_prueba(string $nombre_dueno): array {
    if (!\Iteradores\Configuracion\Entorno::es_pruebas()) {
        return ['exito' => false, 'error' => 'Disponible solo en modo pruebas'];
    }

    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
    if (!$nodo_dueno) {
        return ['exito' => false, 'error' => 'Dueño no encontrado'];
    }

    $nodo_empresas = $nodo_dueno->adyacente('empresas');
    if (!$nodo_empresas) {
        return [
            'exito' => true,
            'empresas_borradas' => [],
            'vehiculos_borrados' => [],
            'cantidad_empresas_borradas' => 0,
            'cantidad_vehiculos_borrados' => 0,
            'salteados' => [],
            'cantidad_salteados' => 0,
        ];
    }

    // Prefijos agregados en v80f: emprevgraf es de las pruebas
    // 37 (eliminar_vehiculo) y 38 (eliminar_empresa). Sin este
    // prefijo, esas empresas quedaban colgadas en el grafo.
    $prefijos_empresa = [
        'fotoauto',
        'viajecambiomicro_emp_',
        'emprevgraf',
    ];
    $prefijos_vehiculo = [
        'fotoauto',
        'viajecambiomicro_veh_',
        'vehgraf',
    ];

    $empresas_borradas = [];
    $vehiculos_borrados = [];
    $salteados = [];

    // Recolectar antes de modificar.
    $empresas = (array) $nodo_empresas->adyacentes();

    foreach ($empresas as $nombre_empresa => $nodo_empresa) {
        $nombre_empresa = (string)$nombre_empresa;

        $empresa_es_prueba = false;
        foreach ($prefijos_empresa as $p) {
            if (stripos($nombre_empresa, $p) === 0) {
                $empresa_es_prueba = true;
                break;
            }
        }

        if ($empresa_es_prueba) {
            $res = eliminar_empresa($nombre_dueno, $nombre_empresa);
            if (is_array($res) && !empty($res['exito'])) {
                $empresas_borradas[] = $nombre_empresa;
            } else {
                $motivo = (is_array($res) && isset($res['error'])) ? $res['error'] : 'Error desconocido';
                $salteados[] = ['nombre' => $nombre_empresa, 'motivo' => $motivo];
            }
            continue;
        }

        // Empresa no de prueba: revisar sus vehículos.
        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) continue;

        $vehiculos = (array) $nodo_vehiculos->adyacentes();
        foreach ($vehiculos as $nombre_vehiculo => $nodo_vehiculo) {
            $nombre_vehiculo = (string)$nombre_vehiculo;

            $vehiculo_es_prueba = false;
            foreach ($prefijos_vehiculo as $p) {
                if (stripos($nombre_vehiculo, $p) === 0) {
                    $vehiculo_es_prueba = true;
                    break;
                }
            }
            if (!$vehiculo_es_prueba) continue;

            $res = eliminar_vehiculo($nombre_empresa, $nombre_vehiculo, $nombre_dueno);
            if (is_array($res) && !empty($res['exito'])) {
                $vehiculos_borrados[] = $nombre_empresa . '/' . $nombre_vehiculo;
            } else {
                $motivo = (is_array($res) && isset($res['error'])) ? $res['error'] : 'Error desconocido';
                $salteados[] = ['nombre' => $nombre_empresa . '/' . $nombre_vehiculo, 'motivo' => $motivo];
            }
        }
    }

    return [
        'exito' => true,
        'empresas_borradas' => $empresas_borradas,
        'vehiculos_borrados' => $vehiculos_borrados,
        'cantidad_empresas_borradas' => count($empresas_borradas),
        'cantidad_vehiculos_borrados' => count($vehiculos_borrados),
        'salteados' => $salteados,
        'cantidad_salteados' => count($salteados),
    ];
}