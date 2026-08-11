<?php
namespace Iteradores\Comunicadores;

use Iteradores\Iteradores\Senal;

/**
 * Define el contrato mínimo que debe cumplir un comunicador.
 *
 * A partir de la versión 1.4.7, los métodos de entrada/salida trabajan
 * directamente con {@link Senal}. La traducción entre bytes y señales
 * se realiza externamente, normalmente por el {@link \Iteradores\Controlador\Talamo}
 * a través de los comandos de comunicación del {@link \Iteradores\Controlador\Controlador}.
 *
 * @author Ignacio David Baigorria
 * 
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 * @version 1.4.7
 */
interface Comunicador
{
    /**
     * Nombre único del comunicador (ej. 'http', 'archivo').
     * @return string
     * @since 1.3.3
     */
    public static function nombre(): string;

    /**
     * Indica si el comunicador solo debe estar disponible en desarrollo.
     * @return bool
     * @since 1.3.3
     */
    public static function solo_desarrollo(): bool;

    /**
     * Breve descripción del comunicador (para ayuda o documentación).
     * @return string
     * @since 1.3.3
     */
    public static function descripcion(): string;

    /**
     * Envía una señal a un destino.
     *
     * @param string $destino Identificador del destino (ruta, URL, etc.).
     * @param Senal  $senal   Señal a transmitir.
     * @return void
     * @since 1.3.3
     * @version 1.4.7
     */
    public function enviar(string $destino, Senal $senal): void;

    /**
     * Solicita datos desde una fuente y los devuelve como señal.
     *
     * @param string $fuente Identificador de la fuente (ruta, URL, etc.).
     * @return Senal Señal que contiene los datos obtenidos.
     * @since 1.3.3
     * @version 1.4.7
     */
    public function solicitar(string $fuente): Senal;

    /**
     * Escucha eventos o mensajes entrantes (modo suscripción).
     *
     * @param callable $callback Función que se ejecutará al recibir un mensaje.
     * @return void
     * @since 1.3.3
     */
    public function escuchar(callable $callback): void;

    /**
     * Cierra los recursos del comunicador (conexiones, sockets, etc.).
     * @return void
     * @since 1.3.3
     */
    public function cerrar(): void;

    /**
     * Devuelve el estado actual del comunicador (ej. 'conectado', 'cerrado').
     * @return string
     * @since 1.3.3
     */
    public function estado(): string;

    /**
     * Configura la autenticación del comunicador.
     *
     * Se invoca automáticamente antes de cada envío o solicitud.
     * Recibe por referencia las opciones de la petición para modificarlas.
     *
     * @param array &$opciones Opciones que se pasarán a enviar/solicitar.
     * @return void
     * @since 1.3.3
     */
    public function autenticar(array &$opciones): void;

    /**
     * Establece credenciales u otros parámetros de autenticación.
     *
     * @param array $credenciales Datos necesarios para autenticarse.
     * @return void
     * @since 1.3.3
     */
    public function establecer_credenciales(array $credenciales): void;
}