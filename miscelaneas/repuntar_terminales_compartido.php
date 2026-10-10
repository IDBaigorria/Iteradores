<?php
/**
 * Migración Fase B2.3.5a: repuntar el enlace `dueno` de cada
 * terminal para que apunte a su contenedor compartido.
 *
 * Delega en el comando `app:repuntar_terminales_compartido`.
 * Idempotente.
 *
 * @since 1.5piloto.77f
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Ejecuta la migración y guarda si hubo cambios.
 *
 * @return array Resumen.
 */
function repuntar_terminales_compartido(): array {
    $res = Controlador::ejecutar_comando(
        'app:repuntar_terminales_compartido',
        ['dueno' => 'todos', 'terminal' => 'todos']
    );

    if (!is_array($res)) {
        return [
            'repuntados' => 0,
            'ya_repuntados' => 0,
            'errores' => ['El comando no devolvió un resumen válido.'],
        ];
    }

    if ($res['repuntados'] > 0) {
        guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    }

    return $res;
}
?>