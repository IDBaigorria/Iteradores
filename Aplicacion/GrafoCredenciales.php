<?php
/**
 * Helper para operar sobre el grafo de credenciales.
 *
 * El grafo de credenciales contiene únicamente:
 * - los nodos usuarios con codigo_hash y contrasena
 * - los nodos de sesiones activas
 *
 * El nombre de usuario (clave del enlace en `usuarios`) es el punto de unión
 * entre el grafo de credenciales y el grafo de la aplicación.
 *
 * @package   Iteradores
 * @since     1.5piloto.70
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

// Flag global anti-reentrada.
$GLOBALS['__en_grafo_credenciales'] = false;

/**
 * Ejecuta un callback dentro del grafo de credenciales.
 *
 * Al entrar:
 * 1. Guarda el grafo de la aplicación.
 * 2. Carga (o crea, si no existe) el grafo de credenciales.
 * 3. Ejecuta el callback.
 * 4. Guarda el grafo de credenciales.
 * 5. Recarga el grafo de la aplicación.
 *
 * Si ya estamos dentro (llamada anidada), ejecuta el callback sin
 * cambiar de grafo.
 *
 * @param callable $fn Callback a ejecutar en el contexto de credenciales.
 * @return mixed El valor devuelto por el callback.
 */
function en_grafo_credenciales(callable $fn) {
    if (!empty($GLOBALS['__en_grafo_credenciales'])) {
        return $fn();
    }
    $GLOBALS['__en_grafo_credenciales'] = true;
    try {
        // Guardar la app actual si tiene nodos.
        if (Nodo::hay_nodos_en_superestructura()) {
            guardar_ambos(Conf::NOMBRE_APP);
        }

        // Crear credenciales si no existe.
        if (!Controlador::existe(Conf::NOMBRE_APP_CREDENCIALES)) {
            Controlador::cargar(Conf::NOMBRE_APP_CREDENCIALES);
            if (!Nodo::nodo_por_id('usuarios')) {
                Nodo::crear_con_id('usuarios');
            }
            if (!Nodo::nodo_por_id('sesiones')) {
                Nodo::crear_con_id('sesiones');
            }
            guardar_ambos(Conf::NOMBRE_APP_CREDENCIALES);
        }

        // Cargar credenciales.
        Controlador::cargar(Conf::NOMBRE_APP_CREDENCIALES);

        // Ejecutar callback.
        $resultado = $fn();

        // Guardar credenciales.
        guardar_ambos(Conf::NOMBRE_APP_CREDENCIALES);

        return $resultado;
    } finally {
        // Recargar la app.
        Controlador::cargar(Conf::NOMBRE_APP);
        $GLOBALS['__en_grafo_credenciales'] = false;
    }
}