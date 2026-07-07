<?php
namespace Iteradores\Controlador;

use Iteradores\Nodos\Matriz2x2;
use Iteradores\Nodos\NodoNumerico;

/**
 * Mapeo directo entre bytes (0‑255) y matrices 2×2 canónicas.
 *
 * Utiliza los primeros 256 números primos de la caché global de
 * {@link NodoNumerico} para generar las matrices primas, sin
 * necesidad de crear nodos intermedios.
 *
 * ## Inicialización
 *
 * El método {@link inicializar()} debe ser invocado **una sola vez**
 * durante el arranque del sistema. {@link \Iteradores\Controlador\Controlador}
 * se encarga de ello en {@link \Iteradores\Controlador\Controlador::inicializar()}.
 *
 * @package Iteradores\Controlador
 * @since 1.4.6
 * @version 1.4.6
 */
class MapeoBytesMatrices
{
    /** @var Matriz2x2[] Mapa byte → Matriz2x2 */
    private static array $byte_a_matriz = [];

    /** @var array<int, int> Mapa primo → byte */
    private static array $matriz_a_byte = [];

    /**
     * Inicializa los mapas a partir de la caché pública de primos.
     *
     * Es seguro llamarla múltiples veces; solo se ejecuta la primera vez.
     *
     * @return void
     */
    public static function inicializar(): void
    {
        if (!empty(self::$byte_a_matriz)) {
            return; // ya está inicializado
        }

        $primos = NodoNumerico::$primos_conocidos;
        for ($byte = 0; $byte < 256; $byte++) {
            $primo = $primos[$byte];
            $matriz = Matriz2x2::crear_prima($primo);
            self::$byte_a_matriz[$byte] = $matriz;
            self::$matriz_a_byte[$primo] = $byte;
        }
    }

    /**
     * Devuelve la matriz prima correspondiente a un byte.
     *
     * @param int $byte Valor entre 0 y 255.
     * @return Matriz2x2|null
     */
    public static function byte_a_matriz(int $byte): ?Matriz2x2
    {
        return self::$byte_a_matriz[$byte] ?? null;
    }

    /**
     * Devuelve el byte correspondiente a una matriz prima canónica.
     *
     * @param Matriz2x2 $matriz
     * @return int|null
     */
    public static function matriz_a_byte(Matriz2x2 $matriz): ?int
    {
        $primo = abs($matriz->a);
        return self::$matriz_a_byte[$primo] ?? null;
    }
}