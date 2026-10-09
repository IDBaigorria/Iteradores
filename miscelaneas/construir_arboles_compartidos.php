<?php
/**
 * Migración Fase B2.3.3: construir los árboles paralelos
 * en los compartidos por terminal.
 *
 * Delega en el comando `app:construir_arboles_compartidos`.
 * Marca cada compartido con `_es_compartido` y reconstruye
 * sus árboles de ventas y cancelaciones con nombres
 * parametrizados. Idempotente.
 *
 * @since 1.5piloto.76x
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Ejecuta la migración y guarda si hubo cambios.
 *
 * @return array Resumen.
 */
function construir_arboles_compartidos(): array {
    $res = Controlador::ejecutar_comando(
        'app:construir_arboles_compartidos',
        ['dueno' => 'todos', 'terminal' => 'todos']
    );

    if (!is_array($res)) {
        return [
            'marcados' => 0,
            'saltados' => 0,
            'errores' => ['El comando no devolvió un resumen válido.'],
        ];
    }

    if ($res['marcados'] > 0) {
        guardar_ambos(ConfiguracionApli::NOMBRE_APP);
    }

    return $res;
}
?>