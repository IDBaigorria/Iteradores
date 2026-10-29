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
 * @version   1.5piloto.83k
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
function en_grafo_credenciales(callable $fn, bool $guardar_app_antes = true) {
    if (!empty($GLOBALS['__en_grafo_credenciales'])) {
        return $fn();
    }
    $GLOBALS['__en_grafo_credenciales'] = true;

    // v78g: capturar los contextos activos de la app para
    // restaurarlos al volver. Sin esto, la carga de credenciales
    // resetea el modo de carga a total, y al recargar la app se
    // pierde el contexto original (guardar_ambos guardaría todo
    // en lugar de solo el contexto).
    $contextos_app_previos = Controlador::contextos_cargados();

    try {
        // Fix 1.5piloto.83j: el bootstrap del admin (que no
        // modifica la app) puede pasar $guardar_app_antes = false
        // para evitar el guardado total que reinserta el grafo
        // entero y cambia los id_fila. Sin este check, cada GET
        // del dueño disparaba un DELETE ALL + INSERT ALL.
        if ($guardar_app_antes && Nodo::hay_nodos_en_superestructura()) {
            guardar_ambos(ConfiguracionApli::NOMBRE_APP);
        }

        // Crear credenciales si no existe.
        if (!Controlador::existe(ConfiguracionApli::NOMBRE_APP_CREDENCIALES)) {
            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
            if (!Nodo::nodo_por_id('usuarios')) {
                Nodo::crear_con_id('usuarios');
            }
            if (!Nodo::nodo_por_id('sesiones')) {
                Nodo::crear_con_id('sesiones');
            }
            guardar_ambos(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
        } else {
            // Solo cargar si ya existe (el caso "no existe" ya la cargó arriba).
            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
        }

        // Ejecutar callback y guardar credenciales en el finally interno,
        // para no perder cambios si el callback lanza una excepción.
        $resultado = null;
        $excepcion = null;
        try {
            $resultado = $fn();
        } catch (\Throwable $e) {
            $excepcion = $e;
        } finally {
            // Fix 1.5piloto.83k: guardar credenciales solo si hubo
            // cambios reales. Si el callback devuelve exactamente
            // `false`, se interpreta como "no modifiqué nada" y no
            // se guarda. Cualquier otro valor (incluido `true`,
            // array, objeto, null) guarda por retrocompatibilidad
            // con los llamadores existentes. Si el callback lanzó
            // excepción, se guarda para no perder los cambios
            // parciales que haya hecho antes de fallar.
            $debe_guardar = ($excepcion !== null) || ($resultado !== false);
            if ($debe_guardar) {
                guardar_ambos(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
            }
        }
        if ($excepcion !== null) {
            throw $excepcion;
        }

        return $resultado;
    } finally {
        // Recargar la app respetando el modo previo (parcial o total).
        // Si falla, es un error fatal: la superestructura quedaría
        // vacía y el próximo guardado podría pisar el grafo.
        if (!empty($contextos_app_previos)) {
            $ok_carga = Controlador::cargar_parcial(ConfiguracionApli::NOMBRE_APP, $contextos_app_previos);
        } else {
            $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);
        }
        $GLOBALS['__en_grafo_credenciales'] = false;
        if (!$ok_carga) {
            Controlador::_error("en_grafo_credenciales: no se pudo recargar la app \"" . ConfiguracionApli::NOMBRE_APP . "\".");
            throw new \RuntimeException("No se pudo recargar el grafo de la aplicacion tras operar en credenciales.");
        }
    }
}

/**
 * Ejecuta un callback dentro del grafo de credenciales, en modo
 * solo lectura.
 *
 * Diferencia con en_grafo_credenciales:
 * - No guarda la app antes de cargar credenciales.
 * - No guarda credenciales al terminar.
 * - Recarga la app al final para dejar el estado como estaba.
 *
 * Pensado para operaciones de diagnóstico (por ejemplo, contar
 * huérfanos) que no modifican el grafo. Es más rápido que el
 * helper completo porque evita la transacción de guardado.
 *
 * Si el callback lanza una excepción, se loguea como error y
 * se devuelve null (no se relanza: es solo lectura).
 *
 * @param callable $fn Callback a ejecutar en el contexto de credenciales.
 * @return mixed El valor devuelto por el callback, o null si hubo error.
 * @since 1.5piloto.76f
 */
function en_grafo_credenciales_solo_lectura(callable $fn) {
    if (!empty($GLOBALS['__en_grafo_credenciales'])) {
        return $fn();
    }
    $GLOBALS['__en_grafo_credenciales'] = true;

    // v78g: idem en_grafo_credenciales. Preservar contextos.
    $contextos_app_previos = Controlador::contextos_cargados();

    try {
        // Crear credenciales si no existe (raro pero posible).
        if (!Controlador::existe(ConfiguracionApli::NOMBRE_APP_CREDENCIALES)) {
            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
            if (!Nodo::nodo_por_id('usuarios')) {
                Nodo::crear_con_id('usuarios');
            }
            if (!Nodo::nodo_por_id('sesiones')) {
                Nodo::crear_con_id('sesiones');
            }
            // No guardamos: solo lectura.
        } else {
            Controlador::cargar(ConfiguracionApli::NOMBRE_APP_CREDENCIALES);
        }

        $resultado = null;
        try {
            $resultado = $fn();
        } catch (\Throwable $e) {
            Controlador::_error("en_grafo_credenciales_solo_lectura: " . $e->getMessage());
        }
        return $resultado;
    } finally {
        // Recargar la app respetando el modo previo (parcial o total).
        if (!empty($contextos_app_previos)) {
            $ok_carga = Controlador::cargar_parcial(ConfiguracionApli::NOMBRE_APP, $contextos_app_previos);
        } else {
            $ok_carga = Controlador::cargar(ConfiguracionApli::NOMBRE_APP);
        }
        $GLOBALS['__en_grafo_credenciales'] = false;
        if (!$ok_carga) {
            Controlador::_error("en_grafo_credenciales_solo_lectura: no se pudo recargar la app \"" . ConfiguracionApli::NOMBRE_APP . "\".");
            throw new \RuntimeException("No se pudo recargar el grafo de la aplicacion tras leer credenciales.");
        }
    }
}