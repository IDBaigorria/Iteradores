<?php
namespace Iteradores\Comunicadores;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\RegistroGlobal;
//require_once(".\Controlador\RegistroGlobal.php");
//require_once(".\Comunicador.php");

/**
 * Comunicador para el sistema de archivos local.
 *
 * Permite leer, escribir, eliminar archivos y listar directorios.
 * Implementa la interfaz {@link Comunicador} y expone métodos adicionales
 * específicos de archivos que también pueden invocarse mediante comandos.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.4
 */
class Archivo implements Comunicador
{
    /** @return string */
    public static function nombre(): string
    {
        return 'archivo';
    }

    /** @return bool */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /** @return string */
    public static function descripcion(): string
    {
        return 'Comunicador para leer, escribir y gestionar archivos locales.';
    }

    /** @inheritdoc */
    public function enviar(string $destino = '', mixed $mensaje = null, array $opciones = []): void
    {
        $accion = $opciones['accion'] ?? 'escribir';
        if ($accion === 'escribir') {
            file_put_contents($destino, (string)$mensaje);
        } elseif ($accion === 'eliminar') {
            if (file_exists($destino)) {
                unlink($destino);
            }
        } elseif ($accion === 'crear_directorio') {
            if (!is_dir($destino)) {
                mkdir($destino, 0777, true);
            }
        }
    }

    /** @inheritdoc */
    public function solicitar(string $destino, mixed $mensaje = null, array $opciones = []): mixed
    {
        $accion = $opciones['accion'] ?? 'leer';
        if ($accion === 'leer') {
            if (!file_exists($destino)) {
                return null;
            }
            return file_get_contents($destino);
        }
        if ($accion === 'listar') {
            if (!is_dir($destino)) {
                return [];
            }
            return scandir($destino);
        }
        return null;
    }

    /** @inheritdoc */
    public function escuchar(callable $callback): void
    {
        // No implementado para archivos locales
    }

    /** @inheritdoc */
    public function cerrar(): void {}

    /** @inheritdoc */
    public function estado(): string
    {
        return 'activo';
    }

    /** @inheritdoc */
    public function autenticar(array &$opciones): void {}

    /** @inheritdoc */
    public function establecer_credenciales(array $credenciales): void {}
}
// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comunicador(Archivo::class);