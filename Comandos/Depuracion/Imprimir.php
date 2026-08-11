<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;
//require_once(__DIR__."\..\Comando.php");
//require_once(__DIR__."\..\..\Controlador\RegistroGlobal.php");

echo "mmmmmmmmmmmm";
/**
 * Comando que imprime errores, alertas y la superestructura.
 *
 * Solo está disponible en entornos de desarrollo y pruebas.
 * No es reversible.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Comandos\Depuracion
 * @since 1.3.1
 * @version 1.3.4
 */
class Imprimir implements Comando
{
    public static function nombre(): string { return 'depuracion:imprimir'; }
    public static function solo_desarrollo(): bool { return true; }

    public static function descripcion(): string
    {
        return 'Muestra los registros de errores, alertas y la superestructura. Sin argumentos, muestra todo.';
    }

    public static function parametros(): array
    {
        return [
            [
                'nombre'      => 'errores',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Muestra solo los errores.',
            ],
            [
                'nombre'      => 'alertas',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Muestra solo las alertas.',
            ],
            [
                'nombre'      => 'super',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Muestra solo la superestructura.',
            ],
        ];
    }

    public static function ejemplos(): array
    {
        return [
            'depuracion:imprimir',
            'depuracion:imprimir --errores --alertas',
            'depuracion:imprimir --super',
        ];
    }

    public function ejecutar(string $token, array $args): bool
    {
        if (!Entorno::permite_pruebas()) {
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Solo disponible en desarrollo/pruebas.");
            }
            return false;
        }

        $banderas = $args['banderas'];
        $mostrar_todo = !$banderas['errores'] && !$banderas['alertas'] && !$banderas['super'];

        if ($mostrar_todo || $banderas['errores']) {
            Objeto::imprimir_errores();
        }
        if ($mostrar_todo || $banderas['alertas']) {
            Objeto::imprimir_alertas();
        }
        if ($mostrar_todo || $banderas['super']) {
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::imprimir_superestructura();
            }
        }
        return true;
    }

    public function reversa(): ?callable { return null; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN: No debe faltar esta línea
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comando(Imprimir::class);