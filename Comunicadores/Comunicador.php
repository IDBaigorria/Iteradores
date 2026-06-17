<?php
namespace Iteradores\Comunicadores;

/**
 * Define el contrato mínimo que debe cumplir un comunicador.
 *
 * Un comunicador es un componente capaz de enviar y recibir datos,
 * opcionalmente convertirlos en {@link \Iteradores\Nodos\Nodo} (a futuro),
 * y gestionar su propia autenticación si es necesario.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 */
interface Comunicador
{
    /**
     * Nombre único del comunicador (ej. 'http', 'salida_estandar').
     * @return string
     */
    public static function nombre(): string;

    /**
     * Indica si el comunicador solo debe estar disponible en desarrollo.
     * @return bool
     */
    public static function solo_desarrollo(): bool;

    /**
     * Breve descripción del comunicador (para ayuda o documentación).
     * @return string
     */
    public static function descripcion(): string;

    /**
     * Envía datos al destino especificado.
     *
     * @param string $destino   Destino del mensaje (URL, canal, etc.).
     * @param mixed  $mensaje   Datos a enviar.
     * @param array  $opciones  Opciones adicionales (headers, timeout, etc.).
     * @return void
     */
    public function enviar(string $destino, mixed $mensaje = null, array $opciones = []): void;

    /**
     * Envía datos y espera una respuesta.
     *
     * @param string $destino   Destino del mensaje.
     * @param mixed  $mensaje   Datos a enviar.
     * @param array  $opciones  Opciones adicionales.
     * @return mixed            Respuesta recibida.
     */
    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed;

    /**
     * Escucha eventos o mensajes entrantes (modo suscripción).
     *
     * @param callable $callback Función que se ejecutará al recibir un mensaje.
     * @return void
     */
    public function escuchar(callable $callback): void;

    /**
     * Cierra los recursos del comunicador (conexiones, sockets, etc.).
     * @return void
     */
    public function cerrar(): void;

    /**
     * Devuelve el estado actual del comunicador (ej. 'conectado', 'cerrado').
     * @return string
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
     */
    public function autenticar(array &$opciones): void;

    /**
     * Establece credenciales u otros parámetros de autenticación.
     *
     * @param array $credenciales Datos necesarios para autenticarse.
     * @return void
     */
    public function establecer_credenciales(array $credenciales): void;
}