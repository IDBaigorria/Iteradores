<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;

/**
 * Comando que limpia las pilas de errores y alertas acumuladas.
 *
 * Sin argumentos, limpia ambas pilas. Se puede limitar la limpieza
 * a una de las dos mediante las banderas `--errores` o `--alertas`.
 *
 * **Entorno:** solo disponible en desarrollo y pruebas.
 * **Reversible:** No.
 *
 * @package Iteradores\Comandos\Depuracion
 * @since 1.3.1
 * @version 1.3.2
 */
class Limpiar implements Comando
{
    public static function nombre(): string { return 'depuracion:limpiar'; }
    public static function solo_desarrollo(): bool { return true; }

    public static function descripcion(): string
    {
        return 'Limpia las pilas de errores y alertas acumuladas. Sin argumentos, limpia ambas.';
    }

    public static function parametros(): array
    {
        return [
            [
                'nombre'      => 'errores',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Limpia solo los errores.',
            ],
            [
                'nombre'      => 'alertas',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Limpia solo las alertas.',
            ],
            [
                'nombre'      => 'todo',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Limpia ambas pilas (explícito, igual que sin argumentos).',
            ],
        ];
    }

    public static function ejemplos(): array
    {
        return [
            'depuracion:limpiar',
            'depuracion:limpiar --errores',
            'depuracion:limpiar --alertas',
            'depuracion:limpiar --todo',
        ];
    }

    public function ejecutar(string $token, array $args): bool
    {
        if (!Entorno::permite_pruebas()) {
            echo "El comando 'depuracion:limpiar' solo está disponible en desarrollo o pruebas.\n";
            return false;
        }

        $banderas = $args['banderas'];
        $limpiarErrores = $banderas['errores'] || $banderas['todo'] || (!$banderas['errores'] && !$banderas['alertas'] && !$banderas['todo']);
        $limpiarAlertas = $banderas['alertas'] || $banderas['todo'] || (!$banderas['errores'] && !$banderas['alertas'] && !$banderas['todo']);

        if ($limpiarErrores) {
            Objeto::limpiar_errores();
            echo "Pila de errores limpiada.\n";
        }
        if ($limpiarAlertas) {
            Objeto::limpiar_alertas();
            echo "Pila de alertas limpiada.\n";
        }

        return true;
    }

    public function reversa(): ?callable { return null; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
Controlador::encolar_comando(Limpiar::class);