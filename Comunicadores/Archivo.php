<?php
namespace Iteradores\Comunicadores;

use Iteradores\Controlador\RegistroGlobal;
use Iteradores\Controlador\Senal;
use Iteradores\Controlador\Talamo;

/**
 * Comunicador para el sistema de archivos local.
 *
 * Permite leer, escribir, eliminar archivos y listar directorios.
 * Implementa la interfaz {@link Comunicador} y expone métodos adicionales
 * específicos de archivos que también pueden invocarse mediante comandos.
 *
 * A partir de la versión 1.4.7, la conversión entre bytes y {@link Senal}
 * se delega en el {@link Talamo}.
 *
 * @package Iteradores\Comunicadores
 * @since 1.3.4
 * @version 1.4.7
 */
class Archivo implements Comunicador
{
    /** @return string
     *  @since 1.3.4 */
    public static function nombre(): string
    {
        return 'archivo';
    }

    /** @return bool
     *  @since 1.3.4 */
    public static function solo_desarrollo(): bool
    {
        return false;
    }

    /** @return string
     *  @since 1.3.4 */
    public static function descripcion(): string
    {
        return 'Comunicador para leer, escribir y gestionar archivos locales.';
    }

    /**
     * Escribe una señal en un archivo.
     *
     * @param string $destino Ruta del archivo a escribir.
     * @param Senal  $senal   Señal cuyos bytes se guardarán.
     * @return void
     * @throws \RuntimeException Si no se puede escribir.
     * @since 1.3.4
     * @version 1.4.7
     */
    public function enviar(string $destino, Senal $senal): void
    {
        $bytes = Talamo::obtener()->traducir_salida($senal);
        file_put_contents($destino, $bytes);
    }

    /**
     * Lee un archivo y lo devuelve como una señal.
     *
     * @param string $fuente Ruta del archivo a leer.
     * @return Senal Señal que contiene los bytes del archivo.
     * @throws \RuntimeException Si el archivo no existe o no se puede leer.
     * @since 1.3.4
     * @version 1.4.7
     */
    public function solicitar(string $fuente): Senal
    {
        if (!file_exists($fuente)) {
            throw new \RuntimeException("El archivo '$fuente' no existe.");
        }
        $contenido = file_get_contents($fuente);
        if ($contenido === false) {
            throw new \RuntimeException("No se pudo leer el archivo '$fuente'.");
        }
        return Talamo::obtener()->traducir_entrada($contenido);
    }

    /**
     * Lista el contenido de un directorio.
     *
     * @param string $directorio Ruta del directorio.
     * @return array Nombres de archivos y directorios.
     * @since 1.4.7
     */
    public function listar(string $directorio): array
    {
        if (!is_dir($directorio)) {
            return [];
        }
        return scandir($directorio);
    }

    /**
     * Elimina un archivo.
     *
     * @param string $ruta Ruta del archivo a eliminar.
     * @return void
     * @since 1.4.7
     */
    public function eliminar(string $ruta): void
    {
        if (file_exists($ruta)) {
            unlink($ruta);
        }
    }

    /**
     * Crea un directorio recursivamente.
     *
     * @param string $ruta Ruta del nuevo directorio.
     * @return void
     * @since 1.4.7
     */
    public function crear_directorio(string $ruta): void
    {
        if (!is_dir($ruta)) {
            mkdir($ruta, 0777, true);
        }
    }

    /** @inheritdoc
     *  @since 1.3.4 */
    public function escuchar(callable $callback): void
    {
        // No implementado para archivos locales
    }

    /** @inheritdoc
     *  @since 1.3.4 */
    public function cerrar(): void {}

    /** @inheritdoc
     *  @since 1.3.4 */
    public function estado(): string
    {
        return 'activo';
    }

    /** @inheritdoc
     *  @since 1.3.4 */
    public function autenticar(array &$opciones): void {}

    /** @inheritdoc
     *  @since 1.3.4 */
    public function establecer_credenciales(array $credenciales): void {}
}
// ═══════════════════════════════════════════════════════════
// AUTOENCOLACIÓN
// ═══════════════════════════════════════════════════════════
RegistroGlobal::encolar_comunicador(Archivo::class);