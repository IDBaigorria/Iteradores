<?php
namespace Iteradores\Controlador;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Matriz2x2;
use Iteradores\Controlador\MapeoBytesMatrices;

/**
 * Señal: portadora mínima de matrices de identidad.
 *
 * A partir de v1.4.7, su responsabilidad se reduce a encapsular una
 * lista de {@link Matriz2x2} y permitir su conversión a/desde bytes.
 * La lógica de consumo, índice de avance y registro de patrones
 * procesados se ha trasladado a {@link \Iteradores\Controlador\Antena}
 * y será gestionada por el futuro Iterador.
 *
 * @package Iteradores
 * @since 1.4.5
 * @version 1.4.7
 */
class Senal extends Objeto
{
    /**
     * Lista de matrices que componen la señal.
     *
     * @var Matriz2x2[]
     */
    private array $matrices;

    /**
     * Constructor.
     *
     * @param Matriz2x2[] $matrices Matrices iniciales (opcional).
     */
    public function __construct(array $matrices = [])
    {
        $this->matrices = $matrices;
    }

    /**
     * Añade una matriz al final de la señal.
     *
     * @param Matriz2x2 $matriz
     * @return void
     */
    public function _matriz(Matriz2x2 $matriz): void
    {
        $this->matrices[] = $matriz;
    }

    /**
     * Devuelve la cantidad de matrices contenidas.
     *
     * @return int
     */
    public function longitud(): int
    {
        return count($this->matrices);
    }

    /**
     * Devuelve todas las matrices de la señal.
     *
     * @return Matriz2x2[]
     */
    public function matrices(): array
    {
        return $this->matrices;
    }

    // ═══════════════════════════════════════════
    // V 1.4.6 – CONVERSIÓN BYTES ↔ SEÑAL
    // ═══════════════════════════════════════════

    /**
     * Construye una señal a partir de una cadena de bytes.
     *
     * Cada byte (0‑255) se traduce a su {@link Matriz2x2} prima canónica
     * mediante {@link MapeoBytesMatrices::byte_a_matriz()}.
     *
     * @param string $bytes Cadena de bytes.
     * @return self Nueva señal con las matrices primas correspondientes.
     * @since 1.4.6
     */
    public static function desde_bytes(string $bytes): self
    {
        $matrices = [];
        $longitud = strlen($bytes);
        for ($i = 0; $i < $longitud; $i++) {
            $byte = ord($bytes[$i]);
            $matriz = MapeoBytesMatrices::byte_a_matriz($byte);
            if ($matriz !== null) {
                $matrices[] = $matriz;
            }
        }
        return new self($matrices);
    }

    /**
     * Convierte una señal en una cadena de bytes.
     *
     * Recorre las matrices de la señal y traduce cada una a su byte
     * original con {@link MapeoBytesMatrices::matriz_a_byte()}.
     *
     * @param Senal $senal Señal a convertir.
     * @return string Cadena de bytes lista para ser escrita o enviada.
     * @since 1.4.6
     * @version 1.4.7
     */
    public static function a_bytes(Senal $senal): string
    {
        $bytes = '';
        foreach ($senal->matrices() as $matriz) {
            $byte = MapeoBytesMatrices::matriz_a_byte($matriz);
            if ($byte !== null) {
                $bytes .= chr($byte);
            }
        }
        return $bytes;
    }
}