<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
/**
 * Registro global de comandos y comunicadores pendientes.
 *
 * Actúa como un buzón temporal donde los archivos de comandos y
 * comunicadores se autoencolan sin necesidad de interactuar directamente
 * con el Controlador, eliminando cualquier posible dependencia circular.
 *
 * El Controlador lee estas listas durante su inicialización y procesa
 * todos los elementos pendientes.
 *
 * @package Iteradores\Controlador
 * @since 1.3.4
 */
class RegistroGlobal extends Objeto
{
    /**
     * Lista de comandos pendientes de registro.
     *
     * Cada entrada es un array asociativo con una de las siguientes estructuras:
     * - ['clase' => string] para comandos con clase (nombre cualificado de la clase).
     * - ['nombre' => string, 'manejador' => callable] para comandos dinámicos.
     *
     * @var array<int, array{clase?: string, nombre?: string, manejador?: callable}>
     */
    public static array $comandos_pendientes = [];

    /**
     * Lista de comunicadores pendientes de registro.
     *
     * Cada entrada es un array asociativo con la clave 'clase'.
     *
     * @var array<int, array{clase: string}>
     */
    public static array $comunicadores_pendientes = [];
    /**
     * Referencia a la clase Controlador una vez inicializada.
     *
     * @var ?string Nombre cualificado de la clase Controlador.
     */
    private static ?string $controlador = null;

    /**
     * Almacena la referencia al Controlador.
     *
     * @param string $clase Nombre completo de la clase Controlador.
     * @return void
     */
    public static function _controlador(string $clase): void
    {
        self::$controlador = $clase;
    }

    /**
     * Obtiene la clase Controlador registrada.
     *
     * Útil para que comandos y otros componentes accedan a los servicios
     * del Controlador sin depender directamente de él.
     *
     * @return string|null Clase Controlador o null si aún no se ha registrado.
     */
    public static function controlador(): ?string
    {
        return self::$controlador;
    }

    /**
     * Encola un comando para su registro posterior.
     *
     * Si se pasa una clase (string), se asume que es un comando con clase
     * que implementa {@link \Iteradores\Comandos\Comando}.
     * Si se pasa un nombre y un manejador, se trata de un comando dinámico.
     *
     * @param string|object $claseONombre Clase del comando (string) o nombre del comando dinámico.
     * @param callable|null $manejador    Función manejadora (solo si se pasa nombre).
     *
     * @return void
     *
     * @example
     * // Comando con clase
     * RegistroGlobal::encolar_comando(Imprimir::class);
     *
     * // Comando dinámico
     * RegistroGlobal::encolar_comando('comunicacion:escribir', function($token, $args) { ... });
     */
    public static function encolar_comando(string|object $claseONombre, ?callable $manejador = null): void
    {
        if (is_string($claseONombre)) {
            if ($manejador !== null) {
                // Comando dinámico: nombre + manejador
                self::$comandos_pendientes[] = [
                    'nombre'    => $claseONombre,
                    'manejador' => $manejador,
                ];
            } else {
                // Comando con clase (string con el nombre cualificado de la clase)
                self::$comandos_pendientes[] = ['clase' => $claseONombre];
            }
        } else {
            // Es un objeto, extraemos su clase
            $clase = get_class($claseONombre);
            self::$comandos_pendientes[] = ['clase' => $clase];
        }
    }

    /**
     * Encola un comunicador para su registro posterior.
     *
     * @param string $clase Nombre cualificado de la clase del comunicador.
     *
     * @return void
     *
     * @example
     * RegistroGlobal::encolar_comunicador(Archivo::class);
     */
    public static function encolar_comunicador(string $clase): void
    {
        self::$comunicadores_pendientes[] = ['clase' => $clase];
    }

    /**
     * Vacía las listas de pendientes.
     *
     * Útil para reiniciar el estado o para pruebas.
     *
     * @return void
     */
    public static function limpiar(): void
    {
        self::$comandos_pendientes = [];
        self::$comunicadores_pendientes = [];
    }
}