<?php
/**
 * Renumeracion de asientos de un micro de viaje.
 *
 * Cambia solo el `dato` de cada nodo asiento existente.
 * No toca pasajeros, ventas, reservas ni estados: el nodo
 * asiento es el mismo, solo cambia el numero visible.
 *
 * Se usa desde el boton "Renumerar asientos" del modal
 * del micro (viajes-micros.js).
 *
 * @package   Iteradores
 * @since     1.5piloto.84h
 * @version   1.5piloto.84h
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");

/**
 * Renumera los asientos de un micro del viaje.
 *
 * @param string $nombre_dueno
 * @param string $nombre_viaje
 * @param string $nombre_micro
 * @param array  $mapa Array de entradas {piso, fila, columna, numero}.
 *                     Debe cubrir todos los asientos del micro.
 * @param ?Nodo  $nodo_contexto Opcional: contexto compartido del terminal.
 * @return array
 */
function renumerar_asientos_micro(
    string $nombre_dueno,
    string $nombre_viaje,
    string $nombre_micro,
    array $mapa,
    ?Nodo $nodo_contexto = null
): array {
    if (empty($mapa)) {
        return ['exito' => false, 'error' => 'No hay cambios para aplicar'];
    }

    // Navegar al micro.
    if ($nodo_contexto === null) {
        $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
        if (!$nodo_dueno) return ['exito' => false, 'error' => 'Dueño no encontrado'];
        $nodo_viajes = $nodo_dueno->adyacente('viajes');
    } else {
        $nodo_viajes = $nodo_contexto->adyacente('viajes');
    }
    if (!$nodo_viajes) return ['exito' => false, 'error' => 'Contenedor de viajes no encontrado'];

    $nodo_viaje = $nodo_viajes->adyacente($nombre_viaje);
    if (!$nodo_viaje) return ['exito' => false, 'error' => 'Viaje no encontrado'];

    $nodo_micros = $nodo_viaje->adyacente('micros');
    if (!$nodo_micros) return ['exito' => false, 'error' => 'El viaje no tiene micros'];

    $nodo_micro = $nodo_micros->adyacente($nombre_micro);
    if (!$nodo_micro) return ['exito' => false, 'error' => 'Micro no encontrado en el viaje'];

    $nodo_copia = $nodo_micro->adyacente('vehiculo_copia');
    if (!$nodo_copia) return ['exito' => false, 'error' => 'No existe copia del vehículo'];

    $nodo_asientos = $nodo_copia->adyacente('asientos');
    if (!$nodo_asientos) return ['exito' => false, 'error' => 'El micro no tiene asientos configurados'];

    // Recolectar asientos por clave "piso|fila|columna".
    $asientos_por_clave = [];
    for ($i = 1; $i <= 2; $i++) {
        $nodo_piso = $nodo_asientos->adyacente("piso_$i");
        if (!$nodo_piso) continue;
        $cabeza = $nodo_piso->adyacente('asientos');
        if (!$cabeza) continue;
        $actual = $cabeza->adyacente('primer');
        $seg = 0;
        while ($actual && $actual->id() !== $cabeza->id() && $seg < 500) {
            $f = $actual->adyacente('fila');
            $c = $actual->adyacente('columna');
            if ($f && $c) {
                $clave = $i . '|' . $f->dato() . '|' . $c->dato();
                $asientos_por_clave[$clave] = $actual;
            }
            $actual = $actual->adyacente('siguiente');
            $seg++;
        }
    }

    if (empty($asientos_por_clave)) {
        return ['exito' => false, 'error' => 'El micro no tiene asientos'];
    }

    // Validar el mapa.
    $claves_mapa = [];
    $numeros_vistos = [];
    foreach ($mapa as $entrada) {
        if (!is_array($entrada)
            || !isset($entrada['piso'], $entrada['fila'], $entrada['columna'], $entrada['numero'])) {
            return ['exito' => false, 'error' => 'Entrada de mapa incompleta'];
        }
        $piso = (string)$entrada['piso'];
        $fila = (string)$entrada['fila'];
        $columna = (string)$entrada['columna'];
        $numero = trim((string)$entrada['numero']);
        if ($numero === '') {
            return ['exito' => false, 'error' => 'Hay un número vacío'];
        }
        $clave = $piso . '|' . $fila . '|' . $columna;
        if (isset($claves_mapa[$clave])) {
            return ['exito' => false, 'error' => "Duplicado en el mapa para ($piso, $fila, $columna)"];
        }
        if (!isset($asientos_por_clave[$clave])) {
            return ['exito' => false, 'error' => "El asiento ($piso, $fila, $columna) no existe en el micro"];
        }
        if (isset($numeros_vistos[$numero])) {
            return ['exito' => false, 'error' => "Número de asiento duplicado: $numero"];
        }
        $claves_mapa[$clave] = true;
        $numeros_vistos[$numero] = true;
    }

    if (count($claves_mapa) !== count($asientos_por_clave)) {
        return ['exito' => false, 'error' => 'Faltan asientos en el mapa: se deben incluir todos los del micro'];
    }

    // Aplicar cambios.
    $cambios = 0;
    foreach ($mapa as $entrada) {
        $clave = (string)$entrada['piso'] . '|' . (string)$entrada['fila'] . '|' . (string)$entrada['columna'];
        $nodo_asiento = $asientos_por_clave[$clave];
        $numero_nuevo = trim((string)$entrada['numero']);
        if ((string)$nodo_asiento->dato() !== $numero_nuevo) {
            $nodo_asiento->_dato($numero_nuevo);
            $cambios++;
        }
    }

    if ($cambios === 0) {
        return ['exito' => true, 'cambios' => 0];
    }

    $ok_guardar = guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    if (!$ok_guardar) {
        log_persistencia("renumerar_asientos_micro: guardar_ambos falló", 'error');
        Controlador::_error("renumerar_asientos_micro: guardar_ambos falló");
        return [
            'exito' => false,
            'error' => 'La renumeración se procesó en memoria pero no se pudo guardar en la base de datos. Revisá el log del servidor o intentá de nuevo.',
        ];
    }

    return ['exito' => true, 'cambios' => $cambios];
}