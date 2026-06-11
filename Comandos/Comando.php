<?php
namespace Iteradores\Comandos;

/**
 * Define el contrato mínimo que debe cumplir un comando ejecutable.
 *
 * Cada comando del sistema implementa esta interfaz, lo que permite
 * que el {@link Controlador} lo registre y ejecute sin conocer su
 * lógica interna.
 *
 * Un comando puede declarar si solo está disponible en desarrollo
 * y, opcionalmente, proporcionar una función de reversa para
 * deshacer sus efectos.
 *
 * @package Iteradores\Comandos
 * @since 1.3.1
 */
interface Comando
{
    /**
     * Nombre único del comando (ej. 'debug:imprimir').
     *
     * @return string
     */
    public static function nombre(): string;

    /**
     * Indica si el comando solo debe registrarse en entorno de desarrollo.
     *
     * @return bool
     */
    public static function solo_desarrollo(): bool;

    /**
     * Ejecuta la lógica del comando.
     *
     * @param string $token Token de seguridad.
     * @param mixed  ...$args Argumentos adicionales.
     * @return mixed Resultado de la ejecución.
     */
    public function ejecutar(string $token, ...$args);

    /**
     * Proporciona una función de reversa, o `null` si el comando no es reversible.
     *
     * La función devuelta debe aceptar el mismo token y argumentos
     * que {@link ejecutar()}.
     *
     * @return callable|null
     */
    public function reversa(): ?callable;
}