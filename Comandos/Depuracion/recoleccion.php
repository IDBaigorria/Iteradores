<?php
namespace Iteradores\Comandos\Depuracion;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nucleo\Objeto;

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
 * @version 1.3.2
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
            echo "El comando 'depuracion:recoleccion' solo está disponible en desarrollo o pruebas.\n";
            return false;
        }

        $posicionales = $args['posicionales'];
        $banderas = $args['banderas'];
        $accion = $posicionales[0] ?? null;
        $afectarErrores = $banderas['errores'] || (!$banderas['errores'] && !$banderas['alertas']);
        $afectarAlertas = $banderas['alertas'] || (!$banderas['errores'] && !$banderas['alertas']);

        if ($accion === 'activar') {
            if ($afectarErrores) {
                Objeto::activar_errores();
                echo "Recolección de errores activada.\n";
            }
            if ($afectarAlertas) {
                Objeto::activar_alertas();
                echo "Recolección de alertas activada.\n";
            }
        } elseif ($accion === 'desactivar') {
            if ($afectarErrores) {
                Objeto::desactivar_errores();
                echo "Recolección de errores desactivada.\n";
            }
            if ($afectarAlertas) {
                Objeto::desactivar_alertas();
                echo "Recolección de alertas desactivada.\n";
            }
        } else {
            echo "Acción no reconocida: '$accion'. Use 'activar' o 'desactivar'.\n";
            return false;
        }

        return true;
    }

    public function reversa(): ?callable { return null; }
}

// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
Controlador::encolar_comando(Recoleccion::class);