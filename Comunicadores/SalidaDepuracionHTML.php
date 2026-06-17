<?php
namespace Iteradores\Comunicadores;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;

/**
 * Comunicador de salida HTML para depuración.
 *
 * Acumula los mensajes en un buffer interno y los muestra como HTML
 * al invocar {@link enviar()}. Útil como salida estándar cuando la
 * aplicación se ejecuta en un navegador.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 */
class SalidaDepuracionHTML implements Comunicador
{
    /** @var array Buffer de mensajes acumulados. */
    private array $buffer = [];

    /** @return string */
    public static function nombre(): string
    {
        return 'salida_depuracion_html';
    }

    /** @return bool */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /** @return string */
    public static function descripcion(): string
    {
        return 'Comunicador de salida HTML para depuración.';
    }

    public function enviar(string $destino = '', mixed $mensaje = null, array $opciones = []): void
    {
        $this->buffer[] = (string)$mensaje;
        echo '<div style="font-family:monospace; margin:0.5em 0;">' . htmlspecialchars((string)$mensaje) . '</div>';
    }

    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed
    {
        $this->buffer[] = (string)$mensaje;
        return null;
    }

    public function escuchar(callable $callback): void {}
    public function cerrar(): void { $this->buffer = []; }
    public function estado(): string { return 'abierto'; }
    public function autenticar(array &$opciones): void {}
    public function establecer_credenciales(array $credenciales): void {}

    public function obtenerBuffer(): string { return implode("\n", $this->buffer); }
    public function limpiarBuffer(): void { $this->buffer = []; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
Controlador::encolar_comunicador(SalidaDepuracionHTML::class);