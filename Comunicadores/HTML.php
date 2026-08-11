<?php
namespace Iteradores\Comunicadores;

use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Iteradores\Senal;
use Iteradores\Controlador\Talamo;

/**
 * Comunicador del sistema para salida HTML.
 *
 * Envía el contenido de una señal a la página web, escapando el HTML
 * y añadiéndolo a un flujo de salida estándar. No admite entrada.
 * La traducción entre bytes y señales se delega en el {@link Talamo}.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3 (anteriormente SalidaDepuracionHTML)
 * @version 1.4.7
 */
class HTML implements Comunicador
{
    /**
     * Buffer de mensajes acumulados.
     *
     * @var array
     */
    private array $buffer = [];

    /**
     * @return string
     * @since 1.3.3
     */
    public static function nombre(): string
    {
        return 'html';
    }

    /**
     * @return bool
     * @since 1.3.3
     */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /**
     * @return string
     * @since 1.3.3
     */
    public static function descripcion(): string
    {
        return 'Comunicador del sistema para salida HTML (solo envío).';
    }

    /**
     * Envía una señal mostrando su contenido como HTML.
     *
     * @param string $destino Ignorado.
     * @param Senal  $senal   Señal cuyos bytes se mostrarán escapados.
     * @return void
     * @since 1.3.3
     * @version 1.4.7
     */
    public function enviar(string $destino, Senal $senal): void
    {
        $texto = Talamo::obtener()->traducir_salida($senal);
        $this->buffer[] = $texto;
        echo '<div style="font-family:monospace; margin:0.5em 0;">'
             . htmlspecialchars($texto)
             . '</div>';
    }

    /**
     * Operación no soportada: HTML es solo de salida.
     *
     * @param string $fuente Ignorado.
     * @return Senal Nunca retorna.
     * @throws \BadMethodCallException Siempre.
     * @since 1.4.7
     */
    public function solicitar(string $fuente): Senal
    {
        throw new \BadMethodCallException('El comunicador HTML no admite lectura.');
    }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function escuchar(callable $callback): void {}

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function cerrar(): void { $this->buffer = []; }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function estado(): string { return 'abierto'; }

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function autenticar(array &$opciones): void {}

    /**
     * @inheritdoc
     * @since 1.3.3
     */
    public function establecer_credenciales(array $credenciales): void {}

    /**
     * Devuelve el contenido acumulado en el buffer.
     *
     * @return string
     * @since 1.3.3
     */
    public function obtenerBuffer(): string { return implode(PHP_EOL, $this->buffer); }

    /**
     * Vacía el buffer interno.
     *
     * @return void
     * @since 1.3.3
     */
    public function limpiarBuffer(): void { $this->buffer = []; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comunicador(HTML::class);