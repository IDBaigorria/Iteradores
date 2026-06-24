<?php
namespace Iteradores\Controlador\interfaces;

/**
 * Contrato para la gestión de dominios en el sistema.
 *
 * Permite activar y desactivar el modo exclusivo de un dominio,
 * haciendo que el péndulo del motor solo itere sobre fases de ese dominio.
 *
 * ## Propósito de la interfaz
 *
 * Esta interfaz existe para documentar y homogeneizar los métodos
 * que el {@link \Iteradores\Controlador\Controlador} expone para
 * controlar los dominios. Por ahora, solo el `Controlador` la implementa.
 *
 * @package Iteradores\Controlador\interfaces
 * @since 1.3.9
 */
interface Dominios
{
    /**
     * Activa el modo exclusivo para un dominio.
     *
     * Mientras un dominio está activo, el péndulo solo itera sobre fases
     * cuyo nombre comience por el prefijo del dominio (ej. 'html:').
     *
     * @param string $dominio Nombre del dominio (sin ':').
     * @return void
     */
    public static function activar_dominio(string $dominio): void;

    /**
     * Desactiva el modo exclusivo de dominio.
     *
     * El péndulo vuelve a considerar todas las fases activas.
     *
     * @return void
     */
    public static function desactivar_dominio(): void;
}