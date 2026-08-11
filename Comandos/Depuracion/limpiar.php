<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;
//require_once(__DIR__."\..\Comando.php");
//require_once(__DIR__."\..\..\Controlador\RegistroGlobal.php");
/**
 * Comando que limpia las pilas de errores y alertas acumuladas.
 *
 * Sin argumentos, limpia ambas pilas. Se puede limitar la limpieza
 * a una de las dos mediante las banderas `--errores` o `--alertas`.
 *
 * **Entorno:** solo disponible en desarrollo y pruebas.
 * **Reversible:** No.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Comandos\Depuracion
 * @since 1.3.1
 * @version 1.3.4
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
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("El comando 'depuracion:limpiar' solo está disponible en desarrollo o pruebas.");
                }
            return false;
        }

        $banderas = $args['banderas'];
        $limpiar_errores = $banderas['errores'] || $banderas['todo'] || (!$banderas['errores'] && !$banderas['alertas'] && !$banderas['todo']);
        $limpiar_alertas = $banderas['alertas'] || $banderas['todo'] || (!$banderas['errores'] && !$banderas['alertas'] && !$banderas['todo']);

        if ($limpiar_errores) {
            Objeto::limpiar_errores();
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Pila de errores limpiada.");
            }
        }
        if ($limpiar_alertas) {
            Objeto::limpiar_alertas();
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Pila de alertas limpiada.");
            }
        }

        return true;
    }

    public function reversa(): ?callable { return null; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comando(Limpiar::class);