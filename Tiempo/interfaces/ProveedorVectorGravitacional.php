<?php
namespace Iteradores\Tiempo\interfaces;

/**
 * Contrato para proveedores de vectores gravitacionales.
 *
 * Un proveedor de vector gravitacional es cualquier clase capaz de
 * devolver un vector tridimensional unitario (x, y, z) que represente
 * de forma determinista la configuración del cielo (Sol, Luna, etc.)
 * para una ubicación geográfica y un instante dado.
 *
 * Este vector puede ser utilizado por los iteradores para "marcar"
 * temporalmente los pesos de las aristas del grafo, permitiendo que
 * el sistema detecte ciclos, mida cercanía temporal y realice
 * predicciones basadas en configuraciones astronómicas pasadas o futuras.
 *
 * ## Propósito de la interfaz
 *
 * Esta interfaz existe principalmente como **documentación ejecutable**
 * para facilitar el entendimiento del sistema tanto a desarrolladores
 * humanos como a inteligencias artificiales. Por ahora, la única clase
 * que la implementa es {@link \Iteradores\Tiempo\RelojAstronomico}.
 * En el futuro podrían añadirse otros proveedores (por ejemplo, uno
 * que incluya más astros o que use efemérides de alta precisión).
 *
 * ## Métodos requeridos
 *
 * - {@link vector}: Obtiene el vector para un instante dado (instancia).
 * - {@link vector_gravitacional}: Obtiene el vector para un instante dado (estático).
 * - {@link _ubicacion}: Actualiza la ubicación geográfica del proveedor.
 *
 * ## Notas de implementación
 *
 * - El vector devuelto debe ser siempre unitario (magnitud 1), salvo
 *   casos extremos donde se puede devolver un vector neutro (0, 0, 1).
 * - La implementación puede incluir caché para optimizar consultas
 *   repetidas con el mismo timestamp.
 * - El método {@link _ubicacion} debe invalidar cualquier caché interna
 *   para forzar el recálculo con las nuevas coordenadas.
 *
 * @package Iteradores\Tiempo
 * @since 1.3.5
 */
interface ProveedorVectorGravitacional
{
    /**
     * Devuelve el vector gravitacional para el instante dado.
     *
     * El vector se calcula a partir de la posición del Sol y la Luna
     * (u otros astros) en el cielo local correspondiente a la ubicación
     * configurada y al timestamp proporcionado.
     *
     * @param int|null $timestamp Marca de tiempo Unix. Si es null, se usa el instante actual.
     * @return array{x: float, y: float, z: float} Vector unitario.
     */
    public function vector(?int $timestamp = null): array;

    /**
     * Actualiza la ubicación geográfica del proveedor.
     *
     * Invalida cualquier caché interna para que la próxima llamada a
     * {@link vector} utilice las nuevas coordenadas.
     *
     * @param float $latitud  Nueva latitud en grados (-90 a 90).
     * @param float $longitud Nueva longitud en grados (-180 a 180).
     * @return void
     */
    public function _ubicacion(float $latitud, float $longitud): void;

    /**
     * Método estático para obtener el vector gravitacional sin necesidad
     * de instanciar un objeto.
     *
     * Es útil cuando no se requiere caché de estado o cuando se necesita
     * un vector para una ubicación distinta a la configurada en la instancia.
     *
     * @param float    $latitud   Latitud en grados.
     * @param float    $longitud  Longitud en grados.
     * @param int|null $timestamp Marca de tiempo Unix. Si es null, se usa el instante actual.
     * @return array{x: float, y: float, z: float} Vector unitario.
     */
    public static function vector_gravitacional(
        float $latitud,
        float $longitud,
        ?int $timestamp = null
    ): array;
}