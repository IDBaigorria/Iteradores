<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;

/**
 * Extiende PerdurarSuperestructura agregando carga y guardado
 * parciales por contexto.
 *
 * Un contexto es un ID especial del grafo (nodo con ID no
 * numérico) que actúa como raíz. El framework no distingue
 * su semántica (usuarios, sesiones, tipos, dueños, etc.):
 * todos son contextos por igual.
 *
 * La interfaz recibe nombres de contextos (IDs especiales),
 * no máscaras. La máscara es un detalle de implementación.
 *
 * @author Ignacio David Baigorria
 * @since 1.5i.7k
 */
interface PerdurarSuperestructuraConContexto extends PerdurarSuperestructura {

    /**
     * Carga solo los nodos que pertenecen a alguno de los
     * contextos pedidos.
     *
     * @param string   $nombre
     * @param string[] $contextos IDs especiales.
     * @return bool|null true=cargó, false=no existe o contexto no registrado, null=error.
     */
    public static function cargar_parcial($nombre, array $contextos): bool|null;

    /**
     * Guarda solo el subgrafo en memoria, filtrando por
     * contextos.
     *
     * @param string   $nombre
     * @param string[] $contextos
     * @return bool
     */
    public static function guardar_parcial($nombre, array $contextos): bool;

    /**
     * Lista los contextos registrados bajo un nombre.
     *
     * @param string $nombre
     * @return string[]|null Array de IDs especiales, o null si no existe.
     */
    public static function listar_contextos($nombre): ?array;
}
?>