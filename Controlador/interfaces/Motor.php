<?php
namespace Iteradores\Controlador\interfaces;

/**
 * Contrato para el motor de ejecución del sistema.
 *
 * El motor es el componente encargado de planificar y ejecutar
 * periódicamente los comandos pendientes en cada fase del sistema.
 * Funciona con un ritmo configurable (ciclos por minuto), un
 * planificador round‑robin (péndulo), soporte para pausas urgentes,
 * y reversa.
 *
 * ## Propósito de la interfaz
 *
 * Esta interfaz existe para documentar y homogeneizar los métodos
 * que el {@link \Iteradores\Controlador\Controlador} expone para
 * controlar el ciclo de vida del motor. Por ahora, solo el
 * `Controlador` la implementa.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Controlador\interfaces
 * @since 1.3.7
 * @version 1.3.9
 */
interface Motor
{
    /**
     * Inicia el motor de ejecución.
     *
     * Si el motor ya está activo o pausado, no hace nada.
     * Arranca el bucle principal que se ejecuta periódicamente
     * según {@link \Iteradores\Configuracion\Conf::MOTOR_INTERVALO_MS}.
     *
     * @return void
     */
    public static function iniciar_motor(): void;

    /**
     * Pausa el motor por solicitud explícita.
     *
     * El estado se conserva para poder reanudar después.
     *
     * @return void
     */
    public static function pausar_motor(): void;

    /**
     * Reanuda el motor tras una pausa explícita.
     *
     * @return void
     */
    public static function reanudar_motor(): void;

    /**
     * Detiene el motor completamente.
     *
     * Limpia el estado interno. Para volver a usar el motor,
     * es necesario llamar a {@link iniciar_motor}.
     *
     * @return void
     */
    public static function detener_motor(): void;

    /**
     * Pausa el motor de forma urgente.
     *
     * Se programa una reanudación automática tras
     * {@link \Iteradores\Configuracion\Conf::MOTOR_PAUSA_URGENTE_TIMEOUT_S}
     * segundos si la pausa no se levanta antes.
     *
     * @param string $razon Motivo de la pausa (para depuración).
     * @return void
     */
    public static function pausar_urgente(string $razon = ''): void;

    /**
     * Añade un comando a la cola de una fase.
     *
     * Si la fase no existe, se crea automáticamente.
     *
     * @param string $fase           Identificador de la fase (ej. "0", "html:entrada:0").
     * @param string $nombre_comando Nombre del comando registrado.
     * @param mixed  ...$args        Argumentos para el comando.
     * @return void
     * @since 1.3.8
     * @version 1.3.9 (cambiada la firma)
     */
    public static function encolar_comando_en_fase(string $fase, string $nombre_comando, ...$args): void;

    /**
     * Deshace el último comando ejecutado por el motor.
     *
     * Solo puede ejecutarse cuando el motor está en estado DETENIDO.
     * Delega en {@link \Iteradores\Controlador\Controlador::deshacer_ultimo()}.
     *
     * @return mixed Resultado de la reversa, o null si no se puede deshacer.
     * @since 1.3.9
     */
    public static function deshacer_motor(): mixed;
}