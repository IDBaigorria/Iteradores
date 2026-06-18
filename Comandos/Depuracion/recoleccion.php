<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;
//require_once(__DIR__."\..\Comando.php");
//require_once(__DIR__."\..\..\Controlador\RegistroGlobal.php");


/**
 * Comando que activa o desactiva la recolección de errores y alertas.
 *
 * El primer argumento posicional debe ser 'activar' o 'desactivar'.
 * Sin banderas adicionales, afecta a ambos sistemas. Se puede limitar
 * el efecto a uno de los dos usando `--errores` o `--alertas`.
 *
 * **Entorno:** solo disponible en desarrollo y pruebas.
 * **Reversible:** No.
 *
 * @package Iteradores\Comandos\Depuracion
 * @since 1.3.1
 * @version 1.3.4
 */
class Recoleccion implements Comando
{
    public static function nombre(): string { return 'depuracion:recoleccion'; }
    public static function solo_desarrollo(): bool { return true; }

    public static function descripcion(): string
    {
        return 'Activa o desactiva la recolección de errores y alertas.';
    }

    public static function parametros(): array
    {
        return [
            [
                'nombre'      => 'accion',
                'tipo'        => 'posicional',
                'obligatorio' => true,
                'descripcion' => 'Acción a realizar: "activar" o "desactivar".',
                'valores'     => ['activar', 'desactivar'],
            ],
            [
                'nombre'      => 'errores',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Afecta solo a la recolección de errores.',
            ],
            [
                'nombre'      => 'alertas',
                'tipo'        => 'bandera',
                'obligatorio' => false,
                'defecto'     => false,
                'descripcion' => 'Afecta solo a la recolección de alertas.',
            ],
        ];
    }

    public static function ejemplos(): array
    {
        return [
            'depuracion:recoleccion activar',
            'depuracion:recoleccion desactivar --errores',
            'depuracion:recoleccion activar --alertas',
            'depuracion:recoleccion desactivar',
        ];
    }

    public function ejecutar(string $token, array $args): bool
    {
        if (!Entorno::permite_pruebas()) {
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("El comando 'depuracion:recoleccion' solo está disponible en desarrollo o pruebas.");
                }
            return false;
        }

        $posicionales = $args['posicionales'];
        $banderas = $args['banderas'];
        $accion = $posicionales[0] ?? null;
        $afectar_errores = $banderas['errores'] || (!$banderas['errores'] && !$banderas['alertas']);
        $afectar_alertas = $banderas['alertas'] || (!$banderas['errores'] && !$banderas['alertas']);

        if ($accion === 'activar') {
            if ($afectar_errores) {
                Objeto::activar_errores();
                $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Recolección de errores activada.");
                }
            }
            if ($afectar_alertas) {
                Objeto::activar_alertas();
                $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Recolección de alertas activada.");
                }
            }
        } elseif ($accion === 'desactivar') {
            if ($afectar_errores) {
                Objeto::desactivar_errores();
                $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Recolección de errores desactivada.");
                }
            }
            if ($afectar_alertas) {
                Objeto::desactivar_alertas();
                $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Recolección de alertas desactivada.");
                }
            }
        } else {
            $controlador = RegistroGlobal::controlador();
            if ($controlador) {
                $controlador::escribir_salida("Acción no reconocida: '$accion'. Use 'activar' o 'desactivar'.");
                }
            return false;
        }

        return true;
    }

    public function reversa(): ?callable { return null; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comando(Recoleccion::class);