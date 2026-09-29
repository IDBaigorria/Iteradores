<?php
/**
 * Helper de persistencia: SQL principal, JSON respaldo.
 *
 * Cada operación de guardado se hace primero en SQL (fuente de verdad)
 * y después en JSON (respaldo). Si el guardado en SQL falla, no se
 * intenta el JSON y se devuelve false. Si el JSON falla, se registra
 * un error con Controlador::_error() y se devuelve true (porque SQL
 * ya está OK).
 *
 * @package   Iteradores
 * @since     1.5piloto.70c
 */

use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;
include_once("./Configuracion/Configuracion.php");
include_once("./Controlador/Controlador.php");
include_once("./Nodos/Nodo.php");

/**
 * Guarda una superestructura en SQL y después en JSON.
 *
 * El JSON es solo respaldo. Si falla, se registra el error con el
 * sistema centralizado de Objeto y la operación sigue siendo exitosa
 * porque SQL ya persistió.
 *
 * @param string $nombre Nombre de la superestructura.
 * @return bool True si el guardado en SQL fue exitoso.
 */
function guardar_ambos($nombre): bool {
    if (!is_string($nombre) || $nombre === '') {
        Controlador::_error("guardar_ambos: nombre invalido");
        return false;
    }

    // 1) Guardar en SQL (fuente de verdad).
    $ok_sql = Controlador::guardar($nombre);
    if (!$ok_sql) {
        return false;
    }

    // 2) Guardar en JSON (respaldo). No debe romper la operación.
    try {
        Controlador::establecer_metodo('JSON');
        $ok_json = Controlador::guardar($nombre);
        if (!$ok_json) {
            Controlador::_error("guardar_ambos: fallo el guardado JSON para el grafo \"$nombre\"");
        }
    } catch (\Throwable $e) {
        Controlador::_error("guardar_ambos: excepcion al guardar JSON para \"$nombre\": " . $e->getMessage());
    } finally {
        Controlador::establecer_metodo('SQL');
    }

    return true;
}