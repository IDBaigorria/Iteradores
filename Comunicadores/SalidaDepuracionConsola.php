<?php
namespace Iteradores\Comunicadores;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;

/**
 * Comunicador de salida para consola (depuración).
 *
 * Acumula los mensajes en un buffer interno y los imprime directamente
 * en la salida estándar (CLI) al invocar {@link enviar()}.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 */
class SalidaDepuracionConsola implements Comunicador
{
    /** @var array Buffer de mensajes acumulados. */
    private array $buffer = [];

    /** @return string */
    public static function nombre(): string
    {
        return 'salida_depuracion_consola';
    }

    /** @return bool */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /** @return string */
    public static function descripcion(): string
    {
        return 'Comunicador de salida para consola (depuración).';
    }

    /** @inheritdoc */
    public function enviar(string $destino = '', mixed $mensaje = null, array $opciones = []): void
    {
        $this->buffer[] = (string)$mensaje;
        echo (string)$mensaje . "\n";
    }

    /** @inheritdoc */
    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed
    {
        $this->buffer[] = (string)$mensaje;
        echo (string)$mensaje . "\n";
        return null;
    }

    /** @inheritdoc */
    public function escuchar(callable $callback): void
    {
        // No aplica para salida unidireccional
    }

    /** @inheritdoc */
    public function cerrar(): void
    {
        $this->buffer = [];
    }

    /** @inheritdoc */
    public function estado(): string
    {
        return 'abierto';
    }

    /** @inheritdoc */
    public function autenticar(array &$opciones): void
    {
        // Sin autenticación
    }

    /** @inheritdoc */
    public function establecer_credenciales(array $credenciales): void
    {
        // Sin credenciales
    }

    /**
     * Devuelve el contenido acumulado en el buffer.
     *
     * @return string
     */
    public function obtenerBuffer(): string
    {
        return implode("\n", $this->buffer);
    }

    /**
     * Vacía el buffer interno.
     *
     * @return void
     */
    public function limpiarBuffer(): void
    {
        $this->buffer = [];
    }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
Controlador::encolar_comunicador(SalidaDepuracionConsola::class);