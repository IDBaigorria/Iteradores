<?php
namespace Iteradores\Nodos\Interfaces;

/**
 * Interfaz que define el manejo de pesos en los enlaces de un nodo.
 *
 * Permite asignar, consultar y ordenar pesos asociados a los enlaces
 * de un nodo. Los pesos pueden ser multidimensionales (diferentes claves).
 *
 * Estos métodos son implementados por la clase
 * {@link ./classes/Iteradores-Nodos-NodoElectrico.html NodoElectrico}.
 *
 * La representación interna de los pesos es opaca para el usuario;
 * la interfaz garantiza el acceso uniforme.
 *
 * @package Iteradores\Nodos\Interfaces
 * @since 1.2.9
 */
interface Peso
{
    /**
     * Asigna o acumula peso en un enlace.
     *
     * Cuando `$acumular` es `true` (comportamiento por defecto), suma el valor dado
     * al peso existente en la dimensión indicada. Si la dimensión no existía, se crea con
     * el valor proporcionado.
     * Cuando `$acumular` es `false`, reemplaza cualquier valor previo por el nuevo peso.
     *
     * Si el enlace no tiene pesos, se realiza la migración perezosa a Enlace automáticamente.
     *
     * @param string      $nombre_enlace Nombre del enlace.
     * @param int|float   $peso          Valor a asignar o sumar (acepta negativos).
     * @param string|null $dimension     Dimensión. `null` para la dimensión por defecto.
     * @param bool        $acumular      `true` para acumular (por defecto), `false` para reemplazar.
     *
     * @return int|float|null Nuevo valor del peso, o `null` si el enlace no existe.
     *
     * @see peso()
     * @see pesos()
     * @since 1.2.9
     */
    public function _peso(string $nombre_enlace, $peso, ?string $dimension = null, bool $acumular = true);

    /**
     * Obtiene el peso de un enlace en una dimensión determinada.
     *
     * Si el enlace no tiene pesos asignados o no existe la dimensión solicitada,
     * devuelve `null`.
     *
     * @param string      $nombre_enlace Nombre del enlace.
     * @param string|null $dimension     Dimensión del peso. Si es null, se usa la por defecto.
     *
     * @return mixed|null El peso almacenado, o `null` si no existe.
     *
     * @see _peso()
     * @since 1.2.9
     */
    public function peso(string $nombre_enlace, ?string $dimension = null);



    /**
     * Devuelve una copia de todos los pesos de un enlace.
     *
     * Retorna un array asociativo con todas las dimensiones y sus valores.
     * Si el enlace no tiene pesos, devuelve un array vacío.
     *
     * @param string $nombre_enlace Nombre del enlace.
     *
     * @return array<string, mixed> Mapa de pesos (dimensión => valor).
     *
     * @see _peso()
     * @since 1.2.9
     */
    public function pesos(string $nombre_enlace): array;

    /**
     * Ordena los adyacentes de la fase actual según el valor del peso en una dimensión.
     *
     * Los enlaces que no poseen la dimensión de peso indicada se colocan al final,
     * conservando su orden original. Cada elemento del array devuelto contiene las claves
     * `nombre_enlace`, `nodo` y `peso` (este último puede ser `null` si no tiene el peso).
     *
     * @param string|null $dimension  Dimensión por la que ordenar. Si es null, se usa la por defecto.
     * @param bool        $ascendente `true` para orden ascendente (por defecto), `false` para descendente.
     * @param bool        $incluir_sin_peso `true` para que devuela al final del array los nodos sin peso en esa dimencion, `false` (por defecto) para que no los devuela
     *
     * @return array Lista de entradas con las claves `nombre_enlace`, `nodo`, `peso`.
     *
     * @see peso()
     * @since 1.2.9
     */
    public function adyacentes_ordenados_por_peso(?string $dimension = null, bool $ascendente = true, bool $incluir_sin_peso = false): array;
}