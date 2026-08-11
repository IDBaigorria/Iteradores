<?php
namespace Iteradores\Iteradores;

use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;

/**
 * Iterador – Navegador y manipulador de estructuras enlazadas.
 *
 * Un Iterador es una herramienta para recorrer, construir, modificar y
 * gestionar estructuras de nodos interconectados mediante **alias** (nombres
 * simbólicos que apuntan a enlaces concretos). Su diseño original, forjado a
 * lo largo de múltiples versiones desde 2013, ha sido refinado para
 * integrarse en el ecosistema del framework *Iteradores*.
 *
 * ## Responsabilidades principales
 *
 * 1. **Gestión del ciclo de vida**: crear una estructura nueva, cargar una
 *    previamente persistida y destruirla cuando ya no sea necesaria.
 * 2. **Alias y navegación**: asignar alias a enlaces, y recorrer la estructura
 *    siguiendo caminos de alias. El nodo actual actúa como cursor de lectura
 *    y escritura.
 * 3. **Manipulación de datos**: leer y escribir datos en los nodos alcanzables
 *    a través de rutas de alias, tanto en la estructura principal como en
 *    almacenes auxiliares (datos individuales).
 * 4. **Construcción desde cadenas**: interpretar una cadena con formato
 *    específico para generar automáticamente una estructura de nodos, y
 *    también serializar una estructura existente de vuelta a cadena.
 * 5. **Clonación**: crear una copia profunda de la estructura interna,
 *    opcionalmente excluyendo ciertos datos individuales.
 * 6. **Control de concurrencia**: marcar un iterador como ocupado para evitar
 *    que dos instancias manipulen simultáneamente la misma estructura
 *    persistida.
 * 7. **Historial de navegación**: registrar opcionalmente los nodos visitados
 *    durante el recorrido, permitiendo volver a posiciones anteriores o
 *    reiniciar el camino.
 *
 * ## Historial de versiones
 *
 * La clase `Iterador` original fue desarrollada por Ignacio David Baigorria
 * a partir de 2013. Alcanzó su madurez conceptual en la versión 2.0 (según
 * la numeración antigua) con un conjunto completo de funcionalidades para
 * navegación, alias, construcción desde cadenas y control de concurrencia.
 * Para el framework *Iteradores* actual, esa versión se considera la **1.0**
 * y sirve como punto de partida para una refactorización completa que la
 * adapte a las convenciones de nomenclatura, la herencia de {@link Objeto}
 * y la integración con el sistema de fases, energía y antenas.
 *
 * A partir de la versión 1.5.0, esta clase base se extenderá en
 * {@link IteradorElectrico} e {@link IteradorNumerico}.
 *
 * @author Ignacio David Baigorria
 *
 * @package Iteradores\Iteradores
 * @since 1.0 (versión original consolidada)
 * @version 1.5.0 (inicio de refactorización)
 * @author Ignacio David Baigorria
 * @extends Objeto
 */
class Iterador extends Objeto
{

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de creación de nodos y validacion de elementos >
	//------------------------------------------------------------------------------->

    /**
     * Verifica si un elemento es válido para ser iterado.
     *
     * En la clase base **todo elemento es válido** (incluyendo `null`, `false` o `0`).
     * Este método está diseñado para ser **sobrescrito en las subclases** cuando
     * se necesite restringir los tipos de elementos que el iterador puede procesar.
     *
     * @param mixed      $elemento Elemento a verificar.
     * @param bool|null &$es_nodo  (Opcional) Parámetro de salida por referencia.
     *                             Se establece en `true` si el elemento ya es una instancia
     *                             de {@link Nodo}, o `false` en caso contrario.
     * @return bool Siempre `true` en {@link Iterador}. Las subclases pueden devolver `false`.
     *
     * @since 1.5.0
     *
     * @see Iterador::nodo() Para entender cómo se utiliza esta validación.
     */
    public static function es_elemento_valido($elemento, &$es_nodo = null): bool
    {
        $es_nodo = $elemento instanceof Nodo;
        return true;
    }

    /**
     * Garantiza que el elemento entregado sea un nodo válido.
     *
     * Este método recibe un valor cualquiera (o ninguno) y asegura que el resultado final
     * sea siempre una instancia de {@link Nodo} (o de la subclase apropiada),
     * siempre que el elemento supere la validación de {@link Iterador::es_elemento_valido()}.
     *
     * **Comportamiento según la entrada:**
     * - Si `$elemento` ya es un nodo válido, lo retorna directamente y asigna `true` a `$es_nodo`.
     * - Si `$elemento` no es un nodo pero es válido, crea uno nuevo mediante
     *   {@link Iterador::crear_nodo_con_dato()}, asigna `false` a `$es_nodo` y lo retorna.
     * - Si `$elemento` no supera la validación, se registra un error y se retorna `null`.
     * - Si se llama sin argumentos (`$elemento = null`), se comporta como el segundo caso,
     *   creando un nodo vacío (si `null` es considerado válido por la subclase).
     *
     * **Personalización en subclases:**
     * Para cambiar las restricciones, basta con sobrescribir {@link es_elemento_valido()}.
     * Para cambiar el tipo de nodo creado, sobrescribir {@link crear_nodo_con_dato()}.
     * Este método (`nodo`) **no necesita ser modificado**, ya que internamente consulta
     * ambos métodos polimórficos.
     *
     * @param mixed      $elemento Valor a encapsular o nodo existente. Si es `null`, se crea un nodo vacío.
     * @param bool|null &$es_nodo  (Opcional) Parámetro de salida por referencia.
     *                             Devuelve `true` si el parámetro original ya era un nodo, `false` en caso contrario.
     * @return Nodo|null Nodo válido que encapsula el valor recibido, o `null` si el elemento no es válido.
     *
     * @since 1.5.0
     *
     * @see Iterador::es_elemento_valido()
     * @see Iterador::crear_nodo_con_dato()
     *
     * @example
     * ```php
     * // Caso 1: elemento no nodo
     * $iterador = new Iterador();
     * $nodo = $iterador->nodo("texto", $esNodo);
     * // $esNodo es false, $nodo->dato() === "texto"
     *
     * // Caso 2: elemento ya es nodo
     * $nodoExistente = Nodo::nodo(42);
     * $nodo = $iterador->nodo($nodoExistente, $esNodo);
     * // $esNodo es true, $nodo === $nodoExistente
     *
     * // Caso 3: elemento inválido (en subclase restrictiva)
     * // Si IteradorNumerico requiere NodoNumerico, pasar un Nodo normal
     * // provocará un error y retornará null.
     * ```
     */
    public function nodo($elemento = null, &$es_nodo = null): ?Nodo
    {
        if (!$this->es_elemento_valido($elemento, $es_nodo)) {
            Iterador::_error("Iterador::nodo(elemento, es_nodo): el elemento no es válido");
            return null;
        }

        if ($es_nodo) {
            return $elemento;
        }

        return $this->crear_nodo_con_dato($elemento);
    }

    /**
     * Crea un nodo del tipo adecuado con el dato proporcionado.
     *
     * Este método es invocado por {@link nodo()} cuando el elemento no es un nodo
     * pero sí es válido. Las subclases deben sobrescribirlo para retornar
     * instancias de su tipo de nodo específico.
     *
     * @param mixed $dato Dato con el que se creará el nodo.
     * @return Nodo Nueva instancia de Nodo (o subclase) que encapsula `$dato`.
     *
     * @since 1.5.0
     */
    protected function crear_nodo_con_dato($dato): Nodo
    {
        return Nodo::nodo($dato);
    }
    
}