<?php
/**
 * Manejador de peticiones POST (API interna).
 *
 * Este archivo es invocado por index.php cuando se recibe una petición POST
 * con el parámetro 'accion'. Su función es delegar el procesamiento al
 * enrutador central de la aplicación, que despachará la acción solicitada
 * a los módulos correspondientes.
 *
 * @package   Iteradores
 * @version   1.5piloto.1
 */

// El framework y los módulos de la aplicación ya fueron cargados en index.php.
// Aquí simplemente ejecutamos el enrutador con los datos recibidos.
enrutar_peticion_post($_POST['accion'] ?? '', $_POST);