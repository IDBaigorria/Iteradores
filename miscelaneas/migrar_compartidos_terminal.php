<?php
/**
 * Migración Fase B2.1: crear los contenedores
 * `compartido_con_us_termX` en cada dueño.
 *
 * Delega en el comando `app:crear_compartidos_terminal`,
 * que tiene el token encapsulado. No toca los enlaces
 * viejos: los compartidos coexisten con la estructura
 * actual. El código sigue usando la raíz `usuarios`.
 *
 * Idempotente: los compartidos que ya existen se saltean.
 *
 * @since 1.5piloto.76q
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Ejecuta la migración y guarda si hubo cambios.
 *
 * @return array Resumen.
 */
function migrar_compartidos_terminal(): array {
    $res = Controlador::ejecutar_comando(
        'app:crear_compartidos_terminal',
        ['dueno' => 'todos', 'terminal' => 'todos']
    );

    if (!is_array($res)) {
        return [
            'creados' => 0,
            'salteados' => 0,
            'errores' => ['El comando no devolvió un resumen válido.'],
        ];
    }

    if ($res['creados'] > 0) {
        guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    }

    return $res;
}
?>