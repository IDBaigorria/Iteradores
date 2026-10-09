<?php
/**
 * Registro de migraciones de la aplicación.
 *
 * Cada migración tiene:
 *   - id (clave del enlace en aplicacion/migraciones)
 *   - nombre, descripcion (para la UI)
 *   - detectar (callable $token → bool)
 *   - aplicar (callable $token → array{exito, detalles})
 *
 * Las funciones de detección y aplicación están en
 * Funciones.php. Este archivo solo las referencia.
 *
 * @since 1.5piloto.76n
 */

require_once __DIR__ . '/Funciones.php';

/**
 * Devuelve el registro de migraciones.
 *
 * @return array<string, array{nombre:string, descripcion:string, detectar:callable, aplicar:callable}>
 */
function migraciones_registradas(): array {
    return [
        'usuarios_especiales' => [
            'nombre' => 'Usuarios como IDs especiales',
            'descripcion' => 'Convierte cada nodo usuario a ID especial us_<nombre>.',
            'detectar' => 'detectar_usuarios_especiales',
            'aplicar' => 'aplicar_migracion_usuarios_especiales',
        ],
        'niveles_usuario' => [
            'nombre' => 'Contenedores publico/privado',
            'descripcion' => 'Crea los contenedores publico y privado en cada usuario.',
            'detectar' => 'detectar_niveles_usuario',
            'aplicar' => 'aplicar_migracion_niveles_usuario',
        ],
        'compartidos_terminal' => [
            'nombre' => 'Compartidos por terminal',
            'descripcion' => 'Crea el contenedor compartido_con_us_termX en cada dueño con terminales autorizados.',
            'detectar' => 'detectar_compartidos_terminal',
            'aplicar' => 'aplicar_migracion_compartidos_terminal',
        ],
    ];
}
?>