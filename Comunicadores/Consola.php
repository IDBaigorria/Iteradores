<?php
namespace Iteradores\Comunicadores;

use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Controlador\Senal;
use Iteradores\Controlador\Talamo;

/**
 * Comunicador del sistema para la consola (entrada/salida estándar).
 *
 * Permite enviar señales a la salida estándar y leer una línea desde
 * la entrada estándar. La traducción entre bytes y señales se delega
 * en el {@link \Iteradores\Controlador\Talamo}.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3 (anteriormente SalidaDepuracionConsola)
 * @version 1.4.7
 */
class Consola implements Comunicador
{
    /**
     * Buffer de mensajes acumulados.
     *
     * @var array
     */
    private array $buffer = [];

    /**
     * Nombre único del comunicador.
     *
     * @return string
     * @since 1.3.3
     */
    public static function nombre(): string
    {
        return 'consola';
    }

    /**
     * Indica si el comunicador solo debe estar disponible en desarrollo.
     *
     * @return bool
     * @since 1.3.3
     */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /**
     * Breve descripción del comunicador.
     *
     * @return string
     * @since 1.3.3
     */
    public static function descripcion(): string
    {
        return 'Comunicador del sistema para entrada/salida por consola.';
    }

    /**
     * Envía una señal a la salida estándar.
     *
     * Traduce la señal a bytes mediante el Tálamo y la imprime
     * directamente en la salida, añadiendo un salto de línea.
     *
     * @param string $destino Ignorado (la consola no tiene destino múltiple).
     * @param Senal  $senal   Señal cuyos bytes se imprimirán.
     * @return void
     * @since 1.3.3
     * @version 1.4.7
     */
    public function enviar(string $destino, Senal $senal): void
    {
        $texto = Talamo::obtener()->traducir_salida($senal);
        $this->buffer[] = $texto;
        echo $texto . PHP_EOL;
    }

    /**
     * Lee una línea desde la entrada estándar y la devuelve como señal.
     *
     * Utiliza el Tálamo para convertir la cadena leída en una {@link Senal}
     * lista para ser procesada por un dominio.
     *
     * @param string $fuente Ignorado (la consola solo lee de STDIN).
     * @return Senal Señal que contiene la línea leída.
     * @since 1.4.7
     */
    public function solicitar(string $fuente): Senal
    {
        $linea = fgets(STDIN);
        $texto = $linea !== false ? rtrim($linea, PHP_EOL) : '';
        return Talamo::obtener()->traducir_entrada($texto);
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function escuchar(callable $callback): void
    {
        // No aplica para entrada/salida unidireccional síncrona
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function cerrar(): void
    {
        $this->buffer = [];
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function estado(): string
    {
        return 'abierto';
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function autenticar(array &$opciones): void
    {
        // Sin autenticación
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function establecer_credenciales(array $credenciales): void
    {
        // Sin credenciales
    }

    /**
     * Devuelve el contenido acumulado en el buffer.
     *
     * @return string
     * @since 1.3.3
     */
    public function obtenerBuffer(): string
    {
        return implode(PHP_EOL, $this->buffer);
    }

    /**
     * Vacía el buffer interno.
     *
     * @return void
     * @since 1.3.3
     */
    public function limpiarBuffer(): void
    {
        $this->buffer = [];
    }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comunicador(Consola::class);