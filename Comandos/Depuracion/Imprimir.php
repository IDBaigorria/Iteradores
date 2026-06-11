<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Configuracion\Conf;
use Iteradores\Nucleo\Objeto;

/**
 * Comando que imprime errores, alertas y la superestructura.
 *
 * Solo está disponible en entornos de desarrollo y pruebas.
 * No es reversible.
 *
 * @package Iteradores\Comandos\Depuracion
 * @since 1.3.1
 */
class Imprimir implements Comando
{
    /** @return string */
    public static function nombre(): string
    {
        return 'debug:imprimir';
    }

    /** @return bool */
    public static function solo_desarrollo(): bool
    {
        return true;
    }

    /**
     * Ejecuta la impresión de diagnóstico.
     *
     * @param string $token
     * @param mixed  ...$args
     * @return bool
     */
    public function ejecutar(string $token, ...$args): bool
    {
        if (!Entorno::permite_pruebas()) {
            echo "El comando 'debug:imprimir' solo está disponible en desarrollo o pruebas.\n";
            return false;
        }

        echo "--- ERRORES ---\n";
        Objeto::imprimir_errores();
        echo "\n--- ALERTAS ---\n";
        Objeto::imprimir_alertas();
        echo "\n--- SUPERESTRUCTURA ---\n";
        Controlador::imprimir_superestructura();
        return true;
    }

    /** @return callable|null */
    public function reversa(): ?callable
    {
        return null; // No reversible
    }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN: No debe faltar esta línea
// ═══════════════════════════════════════════════════════════
Controlador::encolar_comando(Imprimir::class);