<?php
/**
 * Migración Fase B1: crear contenedores `publico` y
 * `privado` en cada usuario.
 *
 * Los contenedores son alias: apuntan a los mismos nodos
 * físicos que hoy cuelgan de la raíz del usuario. Los
 * enlaces viejos NO se tocan. Así el código existente
 * sigue funcionando durante toda la Fase B.
 *
 * Idempotente: los usuarios que ya tienen `publico` se
 * saltean.
 *
 * @since 1.5piloto.76m
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

/**
 * Ejecuta la migración sobre el grafo de la app.
 *
 * @param string $nombre_app Nombre de la superestructura de la app.
 * @param string $usuario_objetivo Nombre de usuario o "todos".
 * @return array Resumen.
 */
function migrar_niveles_usuario(string $nombre_app, string $usuario_objetivo = 'todos'): array {
    // Delega en el comando del framework, que tiene el token
    // encapsulado. Devuelve el resumen directo.
    $res = Controlador::ejecutar_comando(
        'grafo:crear_niveles_usuario',
        ['usuario' => $usuario_objetivo]
    );

    if (!is_array($res)) {
        return [
            'migrados' => 0,
            'saltados' => 0,
            'errores' => ['El comando no devolvió un resumen válido.'],
        ];
    }

    // Guardar solo si hubo cambios.
    if ($res['migrados'] > 0) {
        guardar_ambos($nombre_app);
    }

    return $res;
}
?>