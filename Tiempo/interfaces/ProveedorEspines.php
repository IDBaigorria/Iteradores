<?php
namespace Iteradores\Tiempo\interfaces;

/**
 * Contrato para proveedores de espines.
 *
 * Un proveedor de espines es cualquier clase capaz de devolver, para una
 * ubicación geográfica y un instante dado, un **ramillete de espines**.
 * Cada espin es una entidad individual que combina:
 * - `nombre`: identificador del astro o componente.
 * - `tipo`: categoría ('Astro', 'Eje', 'Prisma', etc.).
 * - `masa`: masa gravitacional o peso contextual.
 * - `vector`: vector unitario en el marco de referencia inercial.
 *
 * A partir de la versión 1.5.2, esta interfaz reemplaza a la antigua
 * `ProveedorVectorGravitacional`. Los proveedores ya no devuelven un único
 * vector combinado; en su lugar, exponen el ramillete completo de espines,
 * permitiendo que el sistema componga llaves matriciales con todos los
 * componentes disponibles.
 *
 * ## Propósito de la interfaz
 *
 * Esta interfaz existe principalmente como **documentación ejecutable**
 * para facilitar el entendimiento del sistema tanto a desarrolladores
 * humanos como a inteligencias artificiales. La primera implementación es
 * {@link \Iteradores\Tiempo\RelojAstronomico}.
 *
 * ## Métodos requeridos
 *
 * - {@link espines}: Obtiene el ramillete de espines para un instante dado.
 * - {@link espin}: Obtiene el espin de un astro específico.
 * - {@link _ubicacion}: Actualiza la ubicación geográfica del proveedor.
 *
 * ## Notas de implementación
 *
 * - Cada espin debe contener un vector unitario (magnitud 1), salvo
 *   casos extremos donde se puede devolver un vector neutro (0, 0, 1).
 * - La implementación puede incluir caché para optimizar consultas
 *   repetidas con el mismo `tiempo_unix`.
 * - El método {@link _ubicacion} debe invalidar cualquier caché interna
 *   para forzar el recálculo con las nuevas coordenadas.
 *
 * @author Ignacio David Baigorria
 * @package Iteradores\Tiempo
 * @since 1.5.2
 * @version 1.5.2
 */
interface ProveedorEspines
{
    /**
     * Devuelve el ramillete de espines para el instante dado.
     *
     * El ramillete es un array de espines, cada uno con la siguiente
     * estructura:
     * ```php
     * [
     *   'nombre' => 'sol',
     *   'tipo'   => 'Astro',
     *   'masa'   => 10.0,
     *   'vector' => ['x' => 0.0, 'y' => 0.0, 'z' => 1.0],
     * ]
     * ```
     *
     * @param int|null $tiempo_unix Tiempo Unix en segundos. Si es null, se usa el instante actual.
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     */
    public function espines(?int $tiempo_unix = null): array;

    /**
     * Devuelve el espin de un astro específico.
     *
     * @param string   $astro       Nombre del astro o componente.
     * @param int|null $tiempo_unix Tiempo Unix en segundos. Si es null, se usa el instante actual.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}|null
     */
    public function espin(string $astro, ?int $tiempo_unix = null): ?array;

    /**
     * Actualiza la ubicación geográfica del proveedor.
     *
     * Invalida cualquier caché interna para que la próxima llamada a
     * {@link espines} o {@link espin} utilice las nuevas coordenadas.
     *
     * @param float $latitud  Nueva latitud en grados (-90 a 90).
     * @param float $longitud Nueva longitud en grados (-180 a 180).
     * @return void
     */
    public function _ubicacion(float $latitud, float $longitud): void;
}