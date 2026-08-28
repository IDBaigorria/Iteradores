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
 * @version 1.5i.4 (inicio de refactorización)
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

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de carga y creación y destrucción-------------->
	//------------------------------------------------------------------------------->  
    /**
     * Nodo cuerpo del iterador. Es el punto de anclaje en la superestructura.
     *
     * @var Nodo|null
     * @since 1.0
     */
    public $raiz_cuerpo = null;

    //METODOS INTERNOS//////
        /**
     * Registra la clase del iterador en la superestructura global.
     *
     * Crea (o reutiliza) el nodo especial "iteradores", el nodo de clase, el nodo de
     * información compartida y el nodo agrupador de iteradores. Finalmente crea el cuerpo
     * del iterador y lo enlaza bajo el nombre dado.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param Iterador $iterador Instancia del iterador a registrar.
     * @param string   $nombrei  Nombre único del iterador.
     * @return Nodo|null El nodo cuerpo del iterador registrado, o null si hubo error.
     */
    static protected function registrar_iterador($iterador, $nombrei)
    {
        // ver si el iterador pertenece a la clase o es heredera de Iterador
        if (!($iterador instanceof Iterador)) {
            Iterador::_error("Iterador::registrar_iterador(iterador) el dato de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
            return null;
        }
        if ($iterador->raiz_cuerpo) {
            Iterador::_error("Iterador::registrar_iterador(iterador) el Iterador pasado por parametro ya fue creado antes");
            return null;
        }

        // ver si existe el nodo con id especial "iteradores"
        if (!$nclases = Nodo::nodo_por_id("iteradores")) {
            $nclases = Nodo::crear_con_id("iteradores");
        }

        // obtener el nombre de la clase
        $nombrec = get_class($iterador);
        // ver si el nombre está registrado
        $nclase = null;
        if (!$nclase = $nclases->adyacente($nombrec)) {
            // registrar
            $nclase = Nodo::crear();
            $nclases->_adyacente_en($nclase, $nombrec);
        }

        // verificar si existe "enlaces permitidos" sino integrarlo
        if (!$nclase->adyacente("alias permitidos")) {
            if ((!$npermitidos = $iterador->_alias_permitidos()) or (!($npermitidos instanceof Nodo))) {
                Iterador::_error("Iterador::registrar_iterador(iterador) error asignando los enlaces permitidos de " . $nombrec);
                return null;
            } else {
                $nclase->_adyacente_en($npermitidos, "alias permitidos");
            }
        }

        // obtener o crear el nodo "informacion compartida"
        $ninformacion = null;
        if (!$ninformacion = $nclase->adyacente("informacion compartida")) {
            $nclase->_adyacente_en($ninformacion = Nodo::crear_con_dato($nombrec), "informacion compartida");
        }

        // obtengo o creo el nodo que apunta a todos los iteradores
        $niteradores = null;
        if (!$niteradores = $nclase->adyacente("iteradores")) {
            $nclase->_adyacente_en($niteradores = Nodo::crear(), "iteradores");
        }
        // verifico si no existe un iterador con ese nombre
        if ($niteradores->adyacente($nombrei)) {
            Iterador::_error("Iterador::registrar_iterador(iterador) ya existe un iterador con ese nombre");
            return null;
        }

        $niteradores->_adyacente_en($cuerpoi = Nodo::crear_con_dato($nombrei), $nombrei);

        $iterador->raiz_cuerpo = $cuerpoi;
        $cuerpoi->_adyacente_en($ninformacion, "clase");

        return $cuerpoi;
    }

    /**
     * Crea un nuevo iterador con el nombre dado.
     *
     * Registra la clase, crea el cuerpo, lo marca ocupado y opcionalmente asigna un elemento
     * como nodo "actual".
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador.
     * @param Iterador $iterador  Instancia del iterador (se pasa por referencia por compatibilidad,
     *                            aunque no se reasigna).
     * @param mixed    $elemento  Elemento opcional para asignar como posición actual.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @return Iterador|null Devuelve el iterador con cuerpo, o null si falló.
     */
    static protected function crear_interno($nombre, &$iterador, $elemento = null, &$es_nodo = null)
    {
        // verifico datos de entrada
        if (!is_string($nombre)) {
            Iterador::_error("Iterador::crear_interno(nombre, iterador, elemento=null, &es_nodo=null) de la clase Iterador, el nombre del iterador debe ser un string");
            return null;
        }

        // registro la clase
        if (!$cuerpo = Iterador::registrar_iterador($iterador, $nombre)) {
            Iterador::_error("Iterador::crear_interno(nombre, iterador, elemento=null, &es_nodo=null) la clase del iterador no es valida");
            return null;
        }

        // lo asigna como ocupado (autoenlace)
        $cuerpo->_adyacente_en($cuerpo, "ocupado");

        // asigno el actual en el caso de que sea valido
        $nodo = null;
        if ($elemento) {
            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {
                Iterador::_error("Iterador::crear_interno(nombre, iterador, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de " . $nombre . " no es valido");
                Iterador::destruir_interno($iterador);
                return null;
            } else {
                $cuerpo->_adyacente_en($nodo, "actual");
            }
        }

        // retorna el iterador
        return $iterador;
    }

    /**
     * Destruye internamente el iterador.
     *
     * Elimina enlaces auxiliares, libera datos y quita el cuerpo del registro global.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param Iterador $iterador Iterador a destruir.
     * @return bool True si se destruyó correctamente, false en caso contrario.
     */
    static protected function destruir_interno($iterador)
    {
        if ((!$cuerpo = $iterador->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
            Iterador::_error("Iterador::destruir_interno(iterador) el iterador no esta ocupado");
            return false;
        }

        // placeholders para limpieza de datos (serán implementados en versiones completas)
        $iterador->destruir_datos();
        $iterador->destruir_datos_individuales();
        $iterador->destruir_datos_temporales();

        if ($nclones = $cuerpo->adyacente("cantidad de clones")) {
            $cuerpo->eliminar_adyacente("cantidad de clones");
            Nodo::eliminar($nclones);
        }
        if ($cuerpo->adyacente("clon")) {
            $cuerpo->eliminar_adyacente("clon");
        }

        $iterador->eliminar_todos_los_alias();

        $cuerpo->eliminar_adyacente("ocupado");

        $its = Nodo::nodo_por_id("iteradores");
        $clase = get_class($iterador);
        $nclase = $its->adyacente($clase);
        $niteradores = $nclase->adyacente("iteradores");
        $niteradores->eliminar_adyacente($cuerpo->dato());

        if (!Nodo::eliminar($cuerpo)) {
            Iterador::_error("Iterador::destruir_interno(iterador) no se pudo destruir el cuerpo del iterador");
            return false;
        }

        return true;
    }
    /**
     * Carga un iterador existente por nombre.
     *
     * Verifica que no esté ocupado, que la clase coincida y asigna el cuerpo.
     * Opcionalmente actualiza la posición actual.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador a cargar.
     * @param Iterador $iterador  Instancia nueva sin cuerpo.
     * @param mixed    $elemento  Elemento opcional para posición actual.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @return Iterador|null Devuelve el iterador con cuerpo, o null si falló.
     */
    static protected function cargar_interno($nombre, $iterador, $elemento = null, &$es_nodo = null)
    {
        // comprobar datos de entrada
        if (!is_string($nombre)) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el nombre del iterador debe ser un string");
            return null;
        }
        if (!($iterador instanceof Iterador)) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
            return null;
        }
        if ($iterador->raiz_cuerpo) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador pasado por parametro ya fue creado antes");
            return null;
        }

        // recuperar nodo iteradores
        if (!$iteradores = Nodo::nodo_por_id("iteradores")) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existen iteradores");
            return null;
        }

        // recupero el nodo clase
        $nombrec = get_class($iterador);
        if (!$nclase = $iteradores->adyacente($nombrec)) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existen iteradores de esa clase");
            return null;
        }

        // recupero el nodo iteradores
        if (!$nits = $nclase->adyacente("iteradores")) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) error interno en la estructura");
            return null;
        }

        // recupero el cuerpo
        if (!$cuerpo = $nits->adyacente($nombre)) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existe ningun iterador con el nombre " . $nombre . "...");
            return null;
        }

        // comprobar ocupado
        if ($cuerpo->adyacente("ocupado")) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador que intenta cargar esta ocupado");
            return null;
        }

        // comprobar clases
        if ((!$clase = $cuerpo->adyacente("clase")) or ($clase->dato() != get_class($iterador))) {
            Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador que se intenta cargar no pertenece a esta clase");
            return null;
        }

        // asignar cuerpo
        $iterador->raiz_cuerpo = $cuerpo;
        // marcar ocupado
        $cuerpo->_adyacente_en($cuerpo, "ocupado");

        // verifico que el elemento de entrada sea valido
        if ($elemento) {
            $nodo = null;
            if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {
                Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de " . $nombre . " no es valido");
                $cuerpo->eliminar_adyacente("ocupado");
                return null;
            } else {
                if ($cuerpo->adyacente("actual")) {
                    Iterador::_alerta("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador ya tenia una posicion actual, de todas formas se asignara la nueva pasada por parametro");
                }
                $cuerpo->_adyacente_en($nodo, "actual");
            }
        }

        return $iterador;
    }

    /**
     * Carga o crea un iterador con el nombre dado.
     *
     * Si existe, lo carga; si no, lo crea. Devuelve por referencia si fue nuevo.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador.
     * @param Iterador $iterador  Instancia nueva sin cuerpo.
     * @param bool|null &$nuevo   Por referencia, true si se creó, false si se cargó.
     * @param mixed    $elemento  Elemento opcional para posición actual.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @return Iterador|null Devuelve el iterador con cuerpo, o null si falló.
     */
    static protected function iterador_interno($nombre, $iterador, &$nuevo = null, $elemento = null, &$es_nodo = null)
    {
        // comprobar datos de entrada
        if (!is_string($nombre)) {
            Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el nombre del iterador debe ser un string");
            return null;
        }
        if (!($iterador instanceof Iterador)) {
            Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
            return null;
        }
        if ($iterador->raiz_cuerpo) {
            Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador pasado por parametro ya fue creado antes");
            return null;
        }

        $nombrec = get_class($iterador);

        if (($iteradores = Nodo::nodo_por_id("iteradores")) and
            ($nclase = $iteradores->adyacente($nombrec)) and
            ($nits = $nclase->adyacente("iteradores")) and
            ($cuerpo = $nits->adyacente($nombre))) {

            // existe, intentamos cargar
            if ($cuerpo->adyacente("ocupado")) {
                Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador que intenta cargar esta ocupado");
                return null;
            }

            if ((!$clase = $cuerpo->adyacente("clase")) or ($clase->dato() != get_class($iterador))) {
                Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador que se intenta cargar no pertenece a esta clase");
                return null;
            }

            // asignar cuerpo
            $iterador->raiz_cuerpo = $cuerpo;
            $cuerpo->_adyacente_en($cuerpo, "ocupado");

            // elemento actual
            if ($elemento) {
                $nodo = null;
                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {
                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de " . $nombre . " no es valido");
                    $cuerpo->eliminar_adyacente("ocupado");
                    return null;
                } else {
                    if ($cuerpo->adyacente("actual")) {
                        Iterador::_alerta("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador ya tenia una posicion actual, de todas formas se asignara la nueva pasada por parametro");
                    }
                    $cuerpo->_adyacente_en($nodo, "actual");
                }
            }

            $nuevo = false;
            return $iterador;

        } else {
            // no existe, crear
            if (!$cuerpo = Iterador::registrar_iterador($iterador, $nombre)) {
                Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) la clase del iterador no es valida");
                return null;
            }

            $cuerpo->_adyacente_en($cuerpo, "ocupado");

            if ($elemento) {
                if (!$nodo = $iterador->nodo($elemento, $es_nodo)) {
                    Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de " . $nombre . " no es valido");
                    Iterador::destruir_interno($iterador);
                    return null;
                } else {
                    $cuerpo->_adyacente_en($nodo, "actual");
                }
            }

            $nuevo = true;
            return $iterador;
        }
    }

    //METODOS PUBLICOS//////////

    /**
     * Crea un nuevo iterador con el nombre dado.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador.
     * @param mixed    $elemento  Elemento inicial opcional.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @return Iterador|null Instancia del iterador creado, o null si falló.
     */
    static public function crear($nombre, $elemento = null, &$es_nodo = null)
    {
        $iter = new Iterador;
        if (!Iterador::crear_interno($nombre, $iter, $elemento, $es_nodo)) {
            Iterador::_error("Iterador::crear(nombre, elemento, &es_nodo) no se pudo crear");
            return null;
        }
        return $iter;
    }

    /**
     * Destruye el iterador actual (instancia).
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @return bool True si se destruyó correctamente, false en caso contrario.
     */
    public function destruir()
    {
        if (!Iterador::destruir_interno($this)) {
            Iterador::_error("Iterador->destruir() no se completo el proceso de destruiccion");
            return false;
        }
        return true;
    }
    /**
     * Carga un iterador existente por nombre.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador.
     * @param mixed    $elemento  Elemento inicial opcional.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @return Iterador|null Instancia del iterador cargado, o null si falló.
     */
    static public function cargar($nombre, $elemento = null, &$es_nodo = null)
    {
        $iter = new Iterador;
        if (!Iterador::cargar_interno($nombre, $iter, $elemento, $es_nodo)) {
            Iterador::_error("Iterador::cargar(nombre, elemento=null, &es_nodo=null) no se pudo cargar");
            return null;
        }
        return $iter;
    }
    /**
     * Carga o crea un iterador con el nombre dado.
     *
     * @since 1.0
     * @version 1.5i.0
     *
     * @param string   $nombre    Nombre del iterador.
     * @param mixed    $elemento  Elemento inicial opcional.
     * @param bool|null &$es_nodo Por referencia, indica si $elemento ya era un Nodo.
     * @param bool|null &$nuevo   Por referencia, true si se creó, false si se cargó.
     * @return Iterador|null Instancia del iterador, o null si falló.
     */
    static public function iterador($nombre, $elemento = null, &$es_nodo = null, &$nuevo = null)
    {
        $iter = new Iterador;
        if (!Iterador::iterador_interno($nombre, $iter, $nuevo, $elemento, $es_nodo)) {
            Iterador::_error("Iterador::iterador(nombre, &nuevo=null, elemento=null, &es_nodo=null, &nuevo=null) no se pudo cargar ni crear el iterador con ese nombre");
            return null;
        }
        return $iter;
    }

    /**
     * Verifica si existe un iterador con el nombre dado en la superestructura.
     *
     * No emite alertas ni errores; simplemente devuelve `true` o `false`.
     *
     * @since 1.0
     * @version 1.5i.4
     *
     * @param string $nombre Nombre del iterador a comprobar.
     * @return bool `true` si existe, `false` en caso contrario.
     */
    static public function existe(string $nombre): bool
    {
        if (!is_string($nombre)) {
            return false;
        }

        $iteradores = Nodo::nodo_por_id("iteradores");
        if (!$iteradores) {
            return false;
        }

        $nombrec = static::class;
        $nclase = $iteradores->adyacente($nombrec);
        if (!$nclase) {
            return false;
        }

        $nits = $nclase->adyacente("iteradores");
        if (!$nits) {
            return false;
        }

        return $nits->adyacente($nombre) !== null;
    }

    //********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ de Propiedades del Iterador ------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	/**
	 * Verifica si el elemento dado es una instancia de Iterador.
	 *
	 * 🔗 Interfaz: Propiedades del Iterador
	 * Caso de uso: saber si un elemento es iterador.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 *
	 * @param mixed $elemento Elemento a comprobar.
	 * @return bool `true` si es un Iterador, `false` en caso contrario.
	 */
	static public function es_iterador($elemento) {
		$es = false;
		if ($elemento instanceof Iterador) {
			$es = true;
		}
		return $es;
	}

	/**
	 * Obtiene el nombre del iterador.
	 *
	 * 🔗 Interfaz: Propiedades del Iterador
	 * Caso de uso: obtener nombre del iterador.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 *
	 * @return string|false El nombre del iterador, `false` si no está ocupado o no tiene cuerpo.
	 */
	public function nombre() {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->nombre() el iterador no esta ocupado");
			return false;
		}
		return $cuerpo->dato();
	}

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ de Marca de ocupado---------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	/**
	 * Notas generales de la interfaz:
	 * Esta interfaz tiene como tarea administrar el acceso a la "marca de ocupado" del iterador.
	 * Esta marca en la realidad no es más que un enlace de la raíz del iterador a sí misma.
	 */

	/**
	 * Activa la "marca de ocupado".
	 *
	 * 🔗 Interfaz: INTERFAZ OCUPAR/DESOCUPAR/OCUPADO
	 * Caso de uso: Activar la "marca de ocupado".
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 *
	 * @return bool `true` si se activó, `false` si ya estaba ocupado o no tiene cuerpo.
	 */
	protected function ocupar() {
		if (!$cuerpo = $this->raiz_cuerpo) {
			$this->_error("Iterador->ocupar() el iterador no tiene cuerpo!!");
			return false;
		}
		if (!$cuerpo->adyacente("ocupado")) {
			$cuerpo->_adyacente_en($cuerpo, "ocupado");
			return true;
		} else {
			$this->_alerta("Iterador->ocupar() el iterador ya esta ocupado");
			return false;
		}
	}

    /**
     * Elimina la "marca de ocupado".
     *
     * 🔗 Interfaz: INTERFAZ OCUPAR/DESOCUPAR/OCUPADO
     * Caso de uso: Desactivar la "marca de ocupado".
     *
     * @since 1.0
     * @version 1.5i.4
     *
     * @return bool `true` si se desactivó, `false` si no estaba ocupado.
     */
    public function desocupar() {
        if (!$cuerpo = $this->raiz_cuerpo) {
            $this->_alerta("Iterador->desocupar() el iterador ya esta desocupado(1)");
            return false;
        }
        if ($cuerpo->adyacente("ocupado")) {
            // Solo elimina la marca de ocupado, sin cambiar la posición actual
            $cuerpo->eliminar_adyacente("ocupado");
            return true;
        } else {
            $this->_alerta("Iterador->desocupar() el iterador ya esta desocupado (2)");
            return false;
        }
    }
	/**
	 * Comprueba si el iterador está ocupado.
	 *
	 * 🔗 Interfaz: INTERFAZ OCUPAR/DESOCUPAR/OCUPADO
	 * Caso de uso: Saber si existe "marca de ocupado".
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 *
	 * @return bool `true` si está ocupado, `false` en caso contrario.
	 */
	public function ocupado() {
		if (($cuerpo = $this->raiz_cuerpo) and ($cuerpo->adyacente("ocupado"))) {
			return true;
		} else {
			return false;
		}
	}


	//********************************************************************************
	//------------------------------------------------------------------------------->
	//----------------------manejo de ALIAS------------------------------------------>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			Esta interfaz administra la relación bidireccional entre alias (nombres simbólicos)
			y enlaces reales dentro del iterador. Se apoya en dos nodos auxiliares:
			- "alias": mapea alias → enlace.
			- "enlaces alias": mapea enlace → alias.
			En la clase base cualquier string es un alias válido y se permite su uso sin restricciones.
			Las subclases pueden sobrescribir _alias_permitidos() y es_alias_valido() para limitar los alias.
		*/

	/**
	 * Devuelve el nodo con los alias permitidos para esta clase.
	 *
	 * En la clase base devuelve un nodo vacío (sin restricciones). Las subclases deben
	 * sobrescribir este método para retornar un nodo cuyos enlaces a sí mismo representen
	 * los alias permitidos.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @return Nodo Nodo con los alias permitidos (vacío por defecto).
	 */
	static protected function _alias_permitidos() {
		return Nodo::crear();
	}

	/**
	 * Verifica si un alias es válido para el iterador dado.
	 *
	 * En la clase base solo se exige que sea un string. Las subclases pueden sobrescribir
	 * este método para aplicar restricciones adicionales (por ejemplo, listas de alias permitidos).
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param mixed  $alias     Alias a validar.
	 * @param Iterador $iterador Iterador sobre el que se valida.
	 * @return bool `true` si es un string válido, `false` en caso contrario.
	 */
	static protected function es_alias_valido($alias, $iterador) {
		if (!is_string($alias)) {
			Iterador::_error("Iterador::es_alias_valido(alias) el alias debe ser un string");
			return false;
		}
		return true;
	}

    /**
     * Verifica si un enlace es válido para el iterador dado.
     *
     * En la clase base se acepta string. Las subclases pueden sobrescribir
     * este método para restringir los enlaces permitidos.
     *
     * @since 1.0
     * @version 1.5i.1
     * @param mixed     $enlace   Enlace a validar.
     * @param Iterador $iterador Iterador sobre el que se valida.
     * @return bool `true` si es válido, `false` en caso contrario.
     */
    static protected function es_enlace_valido($enlace, $iterador) {
        if (!is_string($enlace)) {
            Iterador::_error("Iterador::es_enlace_valido(enlace) el enlace debe ser un string");
            return false;
        }
        return true;
    }

	/**
	 * Asigna un alias a un enlace.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param int|string $enlace Enlace al que se asignará el alias.
	 * @param string     $alias  Alias a asignar.
	 * @return bool `true` si se asignó correctamente, `false` en caso de error.
	 */
	public function _alias($enlace, $alias) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador::_alias(enlace, alias) el iterador no esta ocupado");
			return false;
		}
		if (!Iterador::es_alias_valido($alias, $this)) {
			$this->_error("Iterador::_alias(enlace, alias) el alias no es válido");
			return false;
		}
		// Inicialización perezosa de nodos
		if (!$nalias = $cuerpo->adyacente("alias")) {
			$cuerpo->_adyacente_en($nalias = Nodo::crear(), "alias");
		}
		if (!$nenlacesalias = $cuerpo->adyacente("enlaces alias")) {
			$cuerpo->_adyacente_en($nenlacesalias = Nodo::crear(), "enlaces alias");
		}

		// Si el alias ya existía, actualizar su enlace y eliminar la entrada inversa antigua
		if ($ant = $nalias->adyacente($alias)) {
			$datoant = $ant->dato();
			$ant->_dato($enlace);
			$nodoeli = $nenlacesalias->eliminar_adyacente($datoant);
			if ($nodoeli) Nodo::eliminar($nodoeli);
		} else {
			$nalias->_adyacente_en(Nodo::crear_con_dato($enlace), $alias);
		}

		// Si el enlace ya tenía alias, actualizar y eliminar la entrada directa antigua
		if ($ant = $nenlacesalias->adyacente($enlace)) {
			$datoant = $ant->dato();
			$ant->_dato($alias);
			$nodoeli = $nalias->eliminar_adyacente($datoant);
			if ($nodoeli) Nodo::eliminar($nodoeli);
		} else {
			$nenlacesalias->_adyacente_en(Nodo::crear_con_dato($alias), $enlace);
		}

		return true;
	}

	/**
	 * Elimina un alias individualmente.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param string $alias Alias a eliminar.
	 * @return bool `true` si se eliminó, `false` en caso contrario.
	 */
	public function eliminar_alias($alias) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->eliminar_alias(alias) el iterador no esta ocupado");
			return false;
		}
		if (($todoslosalias = $cuerpo->adyacente("alias")) and ($nodo1 = $todoslosalias->adyacente($alias))) {
			$enlace = $nodo1->dato();
			if (($todoslosenlacesalias = $cuerpo->adyacente("enlaces alias")) and ($nodo2 = $todoslosenlacesalias->adyacente($enlace))) {
				$todoslosalias->eliminar_adyacente($alias);
				Nodo::eliminar($nodo1);
				$todoslosenlacesalias->eliminar_adyacente($enlace);
				Nodo::eliminar($nodo2);
				return true;
			} else {
				$this->_alerta("no existe el alias que intenta eliminar(1)");
				return false;
			}
		} else {
			$this->_alerta("no existe el alias que intenta eliminar(2)");
			return false;
		}
	}

	/**
	 * Asigna varios alias a partir de un arreglo [alias => enlace].
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param array $arreglo_alias Arreglo asociativo donde la clave es el alias y el valor el enlace.
	 * @return bool `true` si se asignaron todos, `false` si alguno falló.
	 */
	public function _varios_alias($arreglo_alias) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->_varios_alias(arreglo_alias) el iterador no esta ocupado");
			return false;
		}
		if (!is_array($arreglo_alias)) {
			$this->_error("Iterador->_varios_alias(arreglo_alias) debe recibir un arreglo cuyas claves sean alias y los valores enlaces");
			return false;
		}
		$error = false;
		foreach ($arreglo_alias as $alias => $enlace) {
			if (!$this->_alias($enlace, $alias)) {
				$error = true;
			}
		}
		if ($error) {
			$this->_error("Iterador->_varios_alias(arreglo_alias) uno o varios pares (alias, enlace) no son válidos");
			return false;
		}
		return true;
	}

	/**
	 * Elimina todos los alias del iterador.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @return bool `true` si se eliminaron (o no había), `false` en caso de error.
	 */
	public function eliminar_todos_los_alias() {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->eliminar_todos_los_alias() el iterador no esta ocupado");
			return false;
		}
		if ((!$nalias = $cuerpo->adyacente("alias")) or (!$nenlacesalias = $cuerpo->adyacente("enlaces alias"))) {
			$this->_alerta("Iterador->eliminar_todos_los_alias() el iterador no tenía alias para eliminar");
			return true;
		}
		if (!$nalias->por_cada_adyacente_ejecutar(
			function($nodo, $enlace, $nalias) {
				$nodoaelim = $nalias->adyacente($enlace);
				$nalias->eliminar_adyacente($enlace);
				if (!Nodo::eliminar($nodoaelim)) {
					$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar algunos alias(1)");
				}
			},
			$nalias
		)) {
			$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar algunos alias(2)");
			return false;
		} else {
			$cuerpo->eliminar_adyacente("alias");
			Nodo::eliminar($nalias);
		}
		if (!$nenlacesalias->por_cada_adyacente_ejecutar(
			function($nodo, $enlace, $nenlacesalias) {
				$nodoaelim = $nenlacesalias->adyacente($enlace);
				$nenlacesalias->eliminar_adyacente($enlace);
				if (!Nodo::eliminar($nodoaelim)) {
					$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar algunos alias(3)");
				}
			},
			$nenlacesalias
		)) {
			$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar algunos alias(4)");
			return false;
		} else {
			$cuerpo->eliminar_adyacente("enlaces alias");
			Nodo::eliminar($nenlacesalias);
		}
		return true;
	}

	/**
	 * Devuelve el enlace correspondiente a un alias.
	 *
	 * Si el alias no está registrado, se devuelve el propio alias (comportamiento base).
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param string|int $alias Alias a traducir.
	 * @return mixed Enlace asociado, o el alias original si no hay traducción.
	 */
    public function enlace($alias) {
        if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
            Iterador::_error("Iterador->enlace($alias) el iterador no esta ocupado");
            return false;
        }
        if (!$this->es_alias_valido($alias, $this)) {
            Iterador::_error("Iterador->enlace($alias) el alias no es válido o no está permitido");
            return false;
        }
        if ($nalias = $cuerpo->adyacente("alias")) {
            if ($nodo = $nalias->adyacente($alias)) {
                return $nodo->dato();
            }
        }
        return $alias;
    }

	/**
	 * Devuelve el alias correspondiente a un enlace.
	 *
	 * Si no hay alias registrado para ese enlace, se devuelve el propio enlace.
	 *
	 * @since 1.0
	 * @version 1.5i.1
	 * @param string|int $enlace Enlace a traducir.
	 * @return mixed Alias asociado, o el enlace original si no hay traducción.
	 */
    public function alias($enlace) {
        if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
            Iterador::_error("Iterador->alias($enlace) el iterador no esta ocupado");
            return false;
        }
        if (!$this->es_enlace_valido($enlace, $this)) {
            Iterador::_error("Iterador->alias($enlace) el enlace no es válido");
            return false;
        }
        if ($nenlacesalias = $cuerpo->adyacente("enlaces alias")) {
            if ($nodo = $nenlacesalias->adyacente($enlace)) {
                return $nodo->dato();
            }
        }
        return $enlace;
    }

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Actual ---------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			Permite obtener y asignar el nodo que representa la posición "actual" del iterador.
			La posición actual es un enlace del cuerpo del iterador con nombre "actual".
		*/

	/**
	 * Obtiene el nodo marcado como la posición "actual" del iterador.
	 *
	 * 🔗 Interfaz: Actual
	 * Caso de uso: Obtener el nodo actual del iterador.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 *
	 * @return Nodo|bool|null Nodo actual, `null` si no hay ninguno, `false` si no está ocupado.
	 */
	public function actual() {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->actual() el iterador no esta ocupado");
			return false;
		}
		$act = $cuerpo->adyacente("actual");
		if (!$act) {
			$this->_alerta("Iterador->actual() el Iterador no tiene asignado ninguna posicion actual");
			return null;
		}
		return $act;
	}

	/**
	 * Asigna la posición "actual" del iterador.
	 *
	 * 🔗 Interfaz: Actual
	 * Caso de uso: Asignar el nodo actual del iterador.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 *
	 * @param mixed      $elemento Elemento a convertir en nodo y asignar como actual. Si es null, se crea un nodo vacío.
	 * @param bool|null &$es_nodo Por referencia, indica si `$elemento` ya era un Nodo.
	 * @return bool|Nodo|null `true` si se asignó correctamente, `false` en caso de error, `null` si falla la conversión.
	 */
    public function _actual($elemento = null, &$es_nodo = null) {
        if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
            Iterador::_error("Iterador->_actual(elemento=null, &es_nodo=null) el iterador no esta ocupado");
            return false;
        }

        // Eliminar nodo actual anterior si existe
        if ($cuerpo->adyacente("actual")) {
            $cuerpo->eliminar_adyacente("actual");
        }

        $nodo = null;
        if (!$nodo = $this->nodo($elemento, $es_nodo)) {
            $this->_error("Iterador _actual(elemento=null, &es_nodo=null), posiblemente elemento no sea valido");
            return null;
        }

        // Si el seguimiento de visitas está activo, se registrará el nodo actual
        if ($cuerpo->adyacente("guardar recorrido")) {
            $ndatos = $this->visitados_auxiliar_crear_obtener_lista($cuerpo);
            $this->guardar_visitado_interno($ndatos, $nodo);
        }

        return $cuerpo->_adyacente_en($nodo, "actual");
    }

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Avanzar --------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			PARA LA VERSION 3.0 se propone que avanzar aumente sus poderes en dos etapas:
				primero: aumentar el poder de movimiento añadiendo un simbolo que indiques opciones de enlaces a seguir basadas en comprarar el dato del nodo actual con un string en la cadena-camino. VER MAQUINAS DE ESTADOS.
				segundo: añadir un simbolo que permita ejecutar una funcion, esta funcion debera estar registrada previamente, con lo cual se propone un registro de funciones y "acortadores de nombres" que permitan referenciarlas desde la cadena-camino. Para ingresarle datos a estas funciones dbeera considerarse utlizar el registro de datos que tiene cada iterador.
		*/

	/**
	 * Convierte una cadena en una estructura de camino (lista enlazada de nodos).
	 *
	 * 🔗 Interfaz: Avanzar
	 * Caso de uso: Convertir cadena en camino de nodos.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 *
	 * @param string $cadena Cadena con sintaxis de camino.
	 * @return Nodo|null Nodo cabeza del camino, o null si hubo error.
	 */
	public function camino($cadena) {
		$i = 0;
		$fin = strlen($cadena);
		$cantidad = 0;

		$eslabonant = Nodo::crear();
		$res = $eslabonant;

		while ($i != $fin) {
			$eslabontext = "";
			$c = $cadena[$i];

			// lo primero que tiene que leer es el alias
			if (($c == ">") or ($c == ";")) {
				$this->_error("Iterador->private camino(cadena). error de sintaxis(1)");
				$this->eliminar_camino($res);
				return null;
			}

			// leo el alias
			if ($c == "/") {
				$i++;
				$eslabontext .= $c;
				if ($i == $fin) {
					$this->_error("Iterador->private camino(cadena). error de sintaxis(2)");
					$this->eliminar_camino($res);
					return null;
				}
			}

			$c = $cadena[$i];
			$aliastext = $c;
			$i++;
			$eslabontext .= $c;
			$fin2 = false;

			while (($i != $fin) and (!$fin2)) {
				$c = $cadena[$i];
				if (($c != ";") && ($c != ">")) {
					if ($c == "/") {
						$i++;
						$eslabontext .= $c;
						if ($i == $fin) {
							$this->_error("Iterador->private camino(cadena). error de sintaxis(3)");
							$this->eliminar_camino($res);
							return null;
						}
						$c = $cadena[$i];
					}
					$aliastext .= $c;
					$i++;
					$eslabontext .= $c;
				} else {
					$fin2 = true;
				}
			}

			$alias = Nodo::crear_con_dato($aliastext);
			$eslabonnue = Nodo::crear();
			$eslabonant->_adyacente_en($eslabonnue, "eslabon");
			$eslabonnue->_adyacente_en($alias, "alias");
			$eslabonant = $eslabonnue;

			// leo simbolo
			$creosim = false;
			$simbolo = null;
			if ($i != $fin) {
				$c = $cadena[$i];
				if ($c != ";") {
					$simbolotext = $c;
					$alias->_adyacente_en($simbolo = Nodo::crear_con_dato($c), "simbolo");
					$i++;
					$eslabontext .= $c;
					$creosim = true;
				}
			}

			// leo parametro
			$parametrotext = "";
			if (($creosim) && ($i != $fin)) {
				$c = $cadena[$i];
				if ($c != ";") {
					if ($c == "/") {
						$i++;
						$eslabontext .= $c;
						if ($i == $fin) {
							$this->_error("Iterador->private camino(cadena). error de sintaxis(2.1)");
							$this->eliminar_camino($res);
							return null;
						}
					}
					$c = $cadena[$i];
					$parametrotext .= $c;
					$i++;
					$eslabontext .= $c;
					$fin3 = false;
					while (($i != $fin) && (!$fin3)) {
						$c = $cadena[$i];
						if (($c != ";") && ($c != ">")) {
							if ($c == "/") {
								$i++;
								$eslabontext .= $c;
								if ($i == $fin) {
									$this->_error("Iterador->private camino(cadena). error de sintaxis(4)");
									$this->eliminar_camino($res);
									return null;
								}
								$c = $cadena[$i];
							}
							$parametrotext .= $c;
							$i++;
							$eslabontext .= $c;
						} else {
							$fin3 = true;
						}
					}
					$simbolo->_adyacente_en(Nodo::crear_con_dato($parametrotext), "parametro");
				}
			}

			// leo punto y coma
			if ($i != $fin) {
				$c = $cadena[$i];
				if ($c != ";") {
					$this->_error("Iterador->private camino(cadena). error de sintaxis(5)");
					$this->eliminar_camino($res);
					return null;
				}
			}
			if ($i != $fin) {
				$i++;
				$eslabontext .= $c;
			}
			$cantidad++;
			$eslabonnue->_dato($eslabontext);
		}

		$res->_dato($cantidad);
		return $res;
	}

	/**
	 * Elimina una estructura de camino completa.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 * @param Nodo $nodo Nodo cabeza del camino.
	 * @return void
	 */
	public function eliminar_camino($nodo) {
		while ($sig = $nodo->adyacente("eslabon")) {
			if (!Nodo::eliminar($nodo)) {
				$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (1)");
			}
			if ($alias = $sig->adyacente("alias")) {
				$sig->eliminar_adyacente("alias");
				if ($sim = $alias->adyacente("simbolo")) {
					$alias->eliminar_adyacente("simbolo");
					if ($par = $sim->adyacente("parametro")) {
						$sim->eliminar_adyacente("parametro");
						if (!Nodo::eliminar($par)) {
							$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (2)");
						}
					}
					if (!Nodo::eliminar($sim)) {
						$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (3)");
					}
				}
				if (!Nodo::eliminar($alias)) {
					$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (4)");
				}
			}
			$nodo = $sig;
		}
		if (!Nodo::eliminar($nodo)) {
			$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (5)");
		}
	}

	/**
	 * Verifica si un carácter es especial para la sintaxis de camino.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 * @param string $caracter Carácter a verificar.
	 * @return bool `true` si es especial.
	 */
	private function avanzar_especial($caracter) {
		$res = false;
		switch ($caracter) {
			case ";":
			case ">":
			case "*":
				$res = true;
		}
		return $res;
	}

	/**
	 * Escapa caracteres especiales anteponiendo `/`.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 * @param string $string Cadena a escapar.
	 * @return string|null Cadena escapada, o `null` si el argumento no es string.
	 */
	public function avanzar_escapar($string) {
		if (!is_string($string)) {
			$this->_error("Iterador->avanzar_escapar(string) el argumento pasado por parametro debe ser un string");
			return null;
		}
		$stringres = "";
		$stringlength = strlen($string);
		for ($pos = 0; $pos < $stringlength; $pos++) {
			$caracter = $string[$pos];
			if ($this->avanzar_especial($caracter) or ($caracter == "/")) {
				$stringres .= "/";
			}
			$stringres .= $caracter;
		}
		return $stringres;
	}

    /**
     * Avanza por el camino indicado, con retroceso si falla.
     *
     * @since 1.0
     * @version 1.5i.2
     *
     * @param string      $cadena           Camino a recorrer.
     * @param int|null    $cant             Cantidad de eslabones a recorrer.
     * @param string|null &$camino_recorrido Por referencia, camino ya recorrido.
     * @param string|null &$camino_restante  Por referencia, camino que resta.
     * @return bool `true` si se completó, `false` en caso de error.
     */
    public function avanzar_interno($cadena, $cant = null, &$camino_recorrido = null, &$camino_restante = null) {
        $cuerpo = $this->raiz_cuerpo;
        $origen = $cuerpo->adyacente("actual");

        // caché de caminos registrados
        if (!$ncaminos = Nodo::nodo_por_id("caminos registrados")) {
            $ncaminos = Nodo::crear_con_id("caminos registrados");
        }
        $yaestaba = false;
        if (!$camino = $ncaminos->adyacente($cadena)) {
            if (!$camino = $this->camino($cadena)) {
                $this->_error("Iterador->avanzar_interno() no se pudo validar la cadena pasada como parametro");
                return false;
            }
        } else {
            $yaestaba = true;
        }

        $camino_recorrido = "";
        $camino_orig = $camino;
        $canttotal = $camino->dato();
        $sobra = false;

        if ($cant and is_int($cant)) {
            if ($cant > 0) {
                if ($cant < $canttotal) {
                    $cantarecorrer = $cant;
                    $sobra = $canttotal - $cant;
                } elseif ($cant == $canttotal) {
                    $cantarecorrer = $canttotal;
                } else {
                    $this->_error("Iterador->avanzar_interno() la cantidad de eslabones de la cadena a recorrer no puede ser mayor al total de eslabones de la cadena (1)");
                    if (!$yaestaba) {
                        $this->eliminar_camino($camino_orig);
                    }
                    return false;
                }
            } else {
                $resaux = $canttotal + $cant;
                if ($resaux < $canttotal) {
                    $cantarecorrer = $resaux;
                    $sobra = -$cant;
                } elseif ($resaux == $canttotal) {
                    $cantarecorrer = $canttotal;
                } else {
                    $this->_error("Iterador->avanzar_interno() la cantidad de eslabones de la cadena a recorrer no puede ser mayor al total de eslabones de la cadena (2)");
                    if (!$yaestaba) {
                        $this->eliminar_camino($camino_orig);
                    }
                    return false;
                }
            }
        } else {
            $cantarecorrer = $canttotal;
        }

        $cantrecorrido = 0;
        while ($camino = $camino->adyacente("eslabon") and ($cantrecorrido < $cantarecorrer)) {
            $alias = $camino->adyacente("alias");
            $simb = $alias->adyacente("simbolo");
            if (!$enlace = $this->enlace($alias = $alias->dato())) {
                $this->_error("Iterador->avanzar_interno() error el alias " . $alias . " no esta permitido. Camino recorrido: " . $camino_recorrido);
                if (!$yaestaba) {
                    $this->eliminar_camino($camino_orig);
                }
                // restaurar actual
                $cuerpo->eliminar_adyacente("actual");
                $cuerpo->_adyacente_en($origen, "actual");
                return false;
            }

            if ($simb == null) {
                $anterior = $cuerpo->adyacente("actual");
                if ($sig = $anterior->adyacente($enlace)) {
                    // eliminar actual anterior y asignar nuevo
                    $cuerpo->eliminar_adyacente("actual");
                    $cuerpo->_adyacente_en($sig, "actual");
                } else {
                    $this->_error("Iterador->avanzar_interno() no existe adyacente en " . $enlace . ". Camino recorrido: " . $camino_recorrido);
                    if (!$yaestaba) {
                        $this->eliminar_camino($camino_orig);
                    }
                    $cuerpo->eliminar_adyacente("actual");
                    $cuerpo->_adyacente_en($origen, "actual");
                    return false;
                }
            } else {
                switch ($simb->dato()) {
                    case ">":
                        if ($nodopar = $simb->adyacente("parametro")) {
                            $par = $nodopar->dato();
                            if (!is_numeric($par)) {
                                $this->_error("Iterador->avanzar_interno() error de sintaxis, el parametro despues de > tiene que ser un numero entero. Camino recorrido: " . $camino_recorrido);
                                if (!$yaestaba) {
                                    $this->eliminar_camino($camino_orig);
                                }
                                $cuerpo->eliminar_adyacente("actual");
                                $cuerpo->_adyacente_en($origen, "actual");
                                return false;
                            }
                            $i = 1;
                            while ($i <= $par) {
                                $anterior = $cuerpo->adyacente("actual");
                                if ($sig = $anterior->adyacente($enlace)) {
                                    $cuerpo->eliminar_adyacente("actual");
                                    $cuerpo->_adyacente_en($sig, "actual");
                                } else {
                                    $this->_error("Iterador->avanzar_interno() no existe adyacente en " . $enlace . ". Camino recorrido: " . $camino_recorrido);
                                    if (!$yaestaba) {
                                        $this->eliminar_camino($camino_orig);
                                    }
                                    $cuerpo->eliminar_adyacente("actual");
                                    $cuerpo->_adyacente_en($origen, "actual");
                                    return false;
                                }
                                $i++;
                            }
                        } else {
                            $fin = false;
                            while (!$fin) {
                                $anterior = $cuerpo->adyacente("actual");
                                if ($sig = $anterior->adyacente($enlace)) {
                                    $cuerpo->eliminar_adyacente("actual");
                                    $cuerpo->_adyacente_en($sig, "actual");
                                } else {
                                    $fin = true;
                                }
                            }
                        }
                        break;
                }
            }
            $cantrecorrido++;
            $camino_recorrido .= $camino->dato();
        }

        if (!$yaestaba) {
            $ncaminos->_adyacente_en($camino_orig, $cadena);
        }
        if ($sobra) {
            $sig = $camino;
            $camino_restante .= $camino->dato();
            while ($sig = $sig->adyacente("eslabon")) {
                $camino_restante .= $sig->dato();
            }
            if (!$ncaminos->adyacente($camino_restante)) {
                $caminofal = Nodo::nodo($sobra);
                $caminofal->_adyacente_en($camino, "eslabon");
                $ncaminos->_adyacente_en($caminofal, $camino_restante);
            }
        }
        return true;
    }

	/**
	 * Avanza por el camino dado, verificando ocupación y posición actual.
	 *
	 * @since 1.0
	 * @version 1.5i.2
	 * @param string      $camino           Camino a recorrer.
	 * @param int|null    $cant             Cantidad de eslabones a recorrer.
	 * @param string|null &$camino_recorrido Por referencia, camino recorrido.
	 * @param string|null &$camino_restante  Por referencia, camino restante.
	 * @return Nodo|bool|null Nodo actual si éxito, `false` si no ocupado, `null` si error.
	 */
	public function avanzar($camino, $cant = null, &$camino_recorrido = null, &$camino_restante = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->avanzar(camino) el iterador no esta ocupado");
			return false;
		}
		if (!$cuerpo->adyacente("actual")) {
			$this->_error("Iterador->avanzar(camino) el iterador no tiene posición actual");
			return null;
		}
		if (!$this->avanzar_interno($camino, $cant, $camino_recorrido, $camino_restante)) {
			$this->_error("Iterador->avanzar(camino) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para más información.");
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
		if ($cuerpo->adyacente("guardar recorrido")) {
			$ndatos = $this->visitados_auxiliar_crear_obtener_lista($cuerpo);
			$this->guardar_visitado_interno($ndatos, $actual);
		}
		return $actual;
	}

    /**
     * Inserta un nodo en el enlace indicado y avanza hasta él.
     *
     * @since 1.0
     * @version 1.5i.2
     * @param string      $alias            Alias del enlace donde insertar.
     * @param mixed       $elemento         Elemento a insertar.
     * @param string|null $camino           Camino previo opcional.
     * @param bool|null  &$es_nodo          Por referencia, indica si elemento era nodo.
     * @param int|null    $cant             Cantidad de eslabones a recorrer.
     * @param string|null &$camino_recorrido Por referencia.
     * @param string|null &$camino_restante  Por referencia.
     * @return Nodo|bool|null Nodo insertado, `false` si no ocupado, `null` en error.
     */
    public function _avanzar($alias, $elemento = null, $camino = null, &$es_nodo = null, $cant = null, &$camino_recorrido = null, &$camino_restante = null) {
        if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
            Iterador::_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) el iterador no esta ocupado");
            return false;
        }
        $origen = null;
        if (!$origen = $cuerpo->adyacente("actual")) {
            $this->_error("Iterador-> _avanzar(alias, elemento, camino, es_nodo) el iterador no tiene asignada una posición actual");
            return null;
        }
        $enlace = null;
        if (!$enlace = $this->enlace($alias)) {
            $this->_error("Iterador-> _avanzar(alias, elemento, camino, es_nodo) no se pudo validar el alias pasado como parametro");
            return null;
        }
        $avanzo = false;
        if (($camino) && (!$avanzo = $this->avanzar_interno($camino, $cant, $camino_recorrido, $camino_restante))) {
            $this->_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para más información.");
            return null;
        }
        if (!$nodo = $this->nodo($elemento, $es_nodo)) {
            $this->_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) no se pudo validar el elemento para agregarlo a la estructura");
            if ($avanzo) {
                $cuerpo->eliminar_adyacente("actual");
                $cuerpo->_adyacente_en($origen, "actual");
            }
            return null;
        }
        $actual = $cuerpo->adyacente("actual");
        if ($actual->adyacente($enlace)) {
            $this->_alerta("Iterador->_avanzar(alias, elemento, camino, es_nodo) se esta reemplazando un nodo en ese enlace");
        }
        $actual->_adyacente_en($nodo, $enlace);
        // actualizar posición actual
        $cuerpo->eliminar_adyacente("actual");
        $cuerpo->_adyacente_en($nodo, "actual");

        if ($cuerpo->adyacente("guardar recorrido")) {
            $ndatos = $this->visitados_auxiliar_crear_obtener_lista($cuerpo);
            $this->guardar_visitado_interno($ndatos, $nodo);
        }
        return $nodo;
    }


	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Adyacente ------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			Permite agregar, obtener y eliminar adyacentes del nodo actual,
			con opción de desplazarse por un camino antes de operar.
		*/

	/**
	 * Agrega un adyacente en un alias, con elemento obligatorio.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Agrega un adyacente en un alias.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param mixed       $elemento  Elemento o nodo a agregar.
	 * @param string|int  $alias     Alias del enlace donde insertar.
	 * @param string|null $camino    Camino opcional a recorrer antes.
	 * @param bool|null  &$es_nodo   Por referencia, indica si $elemento era Nodo.
	 * @return Nodo|null  Nodo agregado, o null si error.
	 */
	public function _adyacente_en($elemento, $alias, $camino = null, &$es_nodo = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) el Iterador no está ocupado!!");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) el iterador no tiene asignado nodo actual");
			return null;
		}
		if (!$enlace = $this->enlace($alias)) {
			$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el alias");
			return null;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) camino no válido");
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
		$nodo = null;
		if (!$nodo = $this->nodo($elemento, $es_nodo)) {
			$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el elemento");
			if ($avanzo) {
				$cuerpo->eliminar_adyacente("actual");
				$cuerpo->_adyacente_en($origen, "actual");
			}
			return null;
		}
        if($actual->adyacente($enlace)){
            $actual->eliminar_adyacente($enlace);
            $this->_alerta("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) se esta reemplazando un nodo en ese enlace");
        }
		$actual->_adyacente_en($nodo, $enlace);
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $nodo;
	}

	/**
	 * Agrega un adyacente en un alias, con elemento opcional.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Agrega un adyacente en un alias 2.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param string|int  $alias     Alias del enlace donde insertar.
	 * @param mixed       $elemento  Elemento o nodo a agregar (opcional).
	 * @param string|null $camino    Camino opcional a recorrer antes.
	 * @param bool|null  &$es_nodo   Por referencia, indica si $elemento era Nodo.
	 * @return Nodo|null  Nodo agregado, o null si error.
	 */
	public function _adyacente($alias, $elemento = null, $camino = null, &$es_nodo = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) el Iterador no está ocupado!!");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) el iterador no tiene asignado nodo actual");
			return null;
		}
		if (!$enlace = $this->enlace($alias)) {
			$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el alias");
			return null;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) camino no válido");
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
		$nodo = null;
		if (!$nodo = $this->nodo($elemento, $es_nodo)) {
			$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el elemento");
			if ($avanzo) {
				$cuerpo->eliminar_adyacente("actual");
				$cuerpo->_adyacente_en($origen, "actual");
			}
			return null;
		}
        if($actual->adyacente($enlace)){
            $actual->eliminar_adyacente($enlace);
            $this->_alerta("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) se esta reemplazando un nodo en ese enlace");
        }
        $actual->_adyacente_en($nodo, $enlace);
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $nodo;
	}

	/**
	 * Agrega varios adyacentes desde un arreglo [alias => elemento].
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Agregar varios adyacentes.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param array       $arreglo_elementos Arreglo asociativo alias => elemento.
	 * @param string|null $camino            Camino opcional.
	 * @return bool       `true` si éxito, `false` si error.
	 */
	public function _adyacentes($arreglo_elementos, $camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->_adyacentes(arreglo_elementos, camino=null) el iterador no esta ocupado");
			return false;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) el iterador no tiene asignado nodo actual");
			return false;
		}
		if (!is_array($arreglo_elementos)) {
			$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) debe recibir un arreglo asociativo alias => elemento");
			return false;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) camino no válido");
			return false;
		}
		$actual = $cuerpo->adyacente("actual");
		$error = false;
		foreach ($arreglo_elementos as $alias => $elemento) {
			$enlace = null;
			$nodo = null;
			if ((!$enlace = $this->enlace($alias)) or (!$nodo = $this->nodo($elemento, $es_nodo))) {
				$error = true;
			} else {
				$actual->_adyacente_en($nodo, $enlace);
			}
		}
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		if ($error) {
			$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) uno o varios pares (alias, elemento) no son válidos");
			return false;
		}
		return true;
	}

	/**
	 * Retorna todos los adyacentes del nodo actual.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Retornar todos los adyacentes.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param string|null $camino Camino opcional.
	 * @return array|null Arreglo [enlace => Nodo] o null si error.
	 */
	public function adyacentes($camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->adyacentes(camino=null) el iterador no esta ocupado");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->adyacentes(camino=null) el iterador no tiene asignado nodo actual");
			return null;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->adyacentes(camino=null) camino no válido");
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
		$res = $actual->por_cada_adyacente_ejecutar(function($nodo) {
			return $nodo;
		});
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $res;
	}

	/**
	 * Retorna un adyacente específico por alias.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Retornar adyacente.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param string|int  $alias  Alias del enlace.
	 * @param string|null $camino Camino opcional.
	 * @return Nodo|null Nodo adyacente o null.
	 */
	public function adyacente($alias, $camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->adyacente(alias, camino=null) el iterador no esta ocupado");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->adyacente(alias, camino=null) el iterador no tiene asignado nodo actual");
			return null;
		}
		if (!$enlace = $this->enlace($alias)) {
			$this->_error("Iterador->adyacente(alias, camino=null) no se pudo validar el alias");
			return null;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->adyacente(alias, camino=null) camino no válido");
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
		$res = $actual->adyacente($enlace);
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		if (!$res) {
			$this->_alerta("Iterador->adyacente(alias, camino=null) no existe adyacente en ese alias");
			return null;
		}
		return $res;
	}

	/**
	 * Elimina un adyacente por alias.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Eliminar un adyacente.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param string|int  $alias  Alias del enlace.
	 * @param string|null $camino Camino opcional.
	 * @return Nodo|bool Nodo eliminado o false.
	 */
	public function eliminar_adyacente($alias, $camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->eliminar_adyacente(alias, camino=null) el iterador no esta ocupado");
			return false;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->eliminar_adyacente(alias, camino=null) el iterador no tiene asignado nodo actual");
			return false;
		}
		if (!$enlace = $this->enlace($alias)) {
			$this->_error("Iterador->eliminar_adyacente(alias, camino=null) no se pudo validar el alias");
			return false;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->eliminar_adyacente(alias, camino=null) camino no válido");
			return false;
		}
		$actual = $cuerpo->adyacente("actual");
		$elim = $actual->eliminar_adyacente($enlace);
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		if (!$elim) {
			$this->_error("Iterador->eliminar_adyacente(alias, camino) puede que no exista nodo en el enlace");
			return false;
		}
		return $elim;
	}

	/**
	 * Elimina todos los adyacentes del nodo actual.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Eliminar todos los adyacentes.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param string|null $camino Camino opcional.
	 * @return bool `true` si éxito, `false` en caso contrario.
	 */
	public function eliminar_adyacentes($camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->eliminar_adyacentes(camino=null) el iterador no esta ocupado");
			return false;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->eliminar_adyacentes(camino=null) el iterador no tiene asignado nodo actual");
			return false;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->eliminar_adyacentes(camino=null) camino no válido");
			return false;
		}
		$actual = $cuerpo->adyacente("actual");
		$res = $actual->eliminar_adyacentes(); // devuelve array

		if (count($res) === 0) {
			if ($avanzo) {
				$cuerpo->eliminar_adyacente("actual");
				$cuerpo->_adyacente_en($origen, "actual");
			}
			$this->_error("Iterador->eliminar_adyacentes(camino=null) no se pudieron eliminar enlaces");
			return false;
		}

		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return true;
	}

	/**
	 * Agrega un enlace desde el elemento hacia la estructura.
	 *
	 * 🔗 Interfaz: Adyacente
	 * Caso de uso: Agrega un enlace desde el elemento a la estructura.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 *
	 * @param mixed       $elemento  Nodo/elemento desde el cual sale el enlace.
	 * @param string|int  $alias     Alias del enlace.
	 * @param string|null $camino    Camino opcional.
	 * @param bool|null  &$es_nodo   Por referencia.
	 * @return Nodo|null Nodo origen, o null en error.
	 */
	public function _como_adyacente_de_nodo_en_alias($elemento, $alias, $camino = null, &$es_nodo = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) el iterador no esta ocupado");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) el iterador no tiene asignado nodo actual");
			return null;
		}
		if (!$enlace = $this->enlace($alias)) {
			$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el alias");
			return null;
		}
		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
            var_dump($avanzo);
			$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) camino no válido");
			return null;
		}
        var_dump($avanzo);
		$nodo = null;
		if (!$nodo = $this->nodo($elemento, $es_nodo)) {
			$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el elemento");
			if ($avanzo) {
				$cuerpo->eliminar_adyacente("actual");
				$cuerpo->_adyacente_en($origen, "actual");
			}
			return null;
		}
		$actual = $cuerpo->adyacente("actual");
        if($nodo->adyacente($enlace)){
            $nodo->eliminar_adyacente($enlace);
            $this->_alerta("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null)  se esta reemplazando un nodo en ese enlace");
        }
        $nodo->_adyacente_en($actual, $enlace);
		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $nodo;
	}

	/**
	 * Versión alternativa de _como_adyacente_de_nodo_en_alias con orden de parámetros cambiado.
	 *
	 * @since 1.0
	 * @version 1.5i.3
	 */
	public function _adyacente_inverso($alias, $elemento = null, $camino = null, &$es_nodo = null) {
		// Simplemente llama a _como_adyacente_de_nodo_en_alias con el orden correcto
		return $this->_como_adyacente_de_nodo_en_alias($elemento, $alias, $camino, $es_nodo);
	}


	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Dato ------------------------------------------>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	/**
	 * Asigna un dato al nodo actual.
	 *
	 * 🔗 Interfaz: Dato
	 * Caso de uso: Asignar dato al nodo actual, con opción de avanzar por un camino.
	 *
	 * @since 1.0
	 * @version 1.5i.4
	 *
	 * @param mixed       $dato   Dato a asignar. No puede ser un Nodo.
	 * @param string|null $camino Camino opcional a recorrer antes de asignar.
	 * @return Nodo|null  Nodo con el dato asignado, o null si error.
	 */
	public function _dato($dato, $camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->_dato(dato, camino=null) el iterador no esta ocupado");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->_dato(dato, camino=null) el iterador no tiene asignado nodo actual");
			return null;
		}

		$es_nodo = null;
		$datoaux = $dato;
		if (!$this->es_elemento_valido($datoaux, $es_nodo)) {
			$this->_error("Iterador->_dato(dato, camino=null) el dato no pasa la prueba es_elemento_valido");
			return null;
		}
		if ($es_nodo) {
			$this->_error("Iterador->_dato(dato, camino=null) el dato es un nodo. No se puede guardar un nodo dentro de un nodo");
			return null;
		}

		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->_dato(dato, camino=null) camino no válido");
			return null;
		}

		$actual = $cuerpo->adyacente("actual");
		$actual->_dato($datoaux);

		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $actual;
	}

	/**
	 * Retorna el dato del nodo actual.
	 *
	 * 🔗 Interfaz: Dato
	 * Caso de uso: Obtener dato del nodo actual, con opción de avanzar.
	 *
	 * @since 1.0
	 * @version 1.5i.4
	 *
	 * @param string|null $camino Camino opcional a recorrer antes de obtener.
	 * @return mixed|null Dato del nodo, o null si error.
	 */
	public function dato($camino = null) {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador->dato(camino=null) el iterador no esta ocupado");
			return null;
		}
		if (!$origen = $cuerpo->adyacente("actual")) {
			$this->_error("Iterador->dato(camino=null) el iterador no tiene asignado nodo actual");
			return null;
		}

		$avanzo = false;
		if (($camino) && (!$avanzo = $this->avanzar_interno($camino))) {
			$this->_error("Iterador->dato(camino=null) camino no válido");
			return null;
		}

		$res = $cuerpo->adyacente("actual")->dato();

		if ($avanzo) {
			$cuerpo->eliminar_adyacente("actual");
			$cuerpo->_adyacente_en($origen, "actual");
		}
		return $res;
	}

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Liberar ////////////////////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	/**
	 * Libera el nodo actual, estableciendo como actual el propio cuerpo.
	 *
	 * 🔗 Interfaz: Liberar
	 * Caso de uso: Liberar el nodo actual.
	 *
	 * @since 1.0
	 * @version 1.5i.4
	 *
	 * @return Nodo|bool|null Nodo que era actual, `false` si no ocupado, `null` si ya estaba liberado.
	 */
	public function liberar() {
		if ((!$cuerpo = $this->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))) {
			Iterador::_error("Iterador::liberar() el iterador no esta ocupado");
			return false;
		}
		$act = $cuerpo->adyacente("actual");
		if ($act === $cuerpo) {
			$this->_error("Iterador->liberar() el Iterador ya estaba liberado");
			return null;
		}
		// Eliminar actual anterior y asignar cuerpo
		if ($act) {
			$cuerpo->eliminar_adyacente("actual");
		}
		$cuerpo->_adyacente_en($cuerpo, "actual");
		return $act;
	}



    //placeholder
    
        /**
     * Placeholder: crea u obtiene la lista auxiliar de visitados.
     * @since 1.0
     * @version 1.5i.2
     * @return mixed
     */
    protected function visitados_auxiliar_crear_obtener_lista($cuerpo) {
        return null; // Pendiente de implementación
    }

    /**
     * Placeholder: guarda un nodo visitado en la lista.
     * @since 1.0
     * @version 1.5i.2
     * @return void
     */
    protected function guardar_visitado_interno($lista, $nodo) {
        // Pendiente de implementación
    }

    /**
     * Devuelve el nodo de alias permitidos para la clase.
     * Placeholder: debe ser implementado por subclases si se requiere.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return Nodo|null
     */
    //protected function _alias_permitidos() { return Nodo::nodo("hola"); }

    /**
     * Libera los datos asociados al iterador.
     * Placeholder: debe ser implementado por subclases.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return void
     */
    protected function destruir_datos() {}

    /**
     * Libera los datos individuales.
     * Placeholder: debe ser implementado por subclases.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return void
     */
    protected function destruir_datos_individuales() {}

    /**
     * Libera los datos temporales.
     * Placeholder: debe ser implementado por subclases.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return void
     */
    protected function destruir_datos_temporales() {}

    /**
     * Elimina todos los alias del iterador.
     * Placeholder: debe ser implementado por subclases.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return void
     */
   // protected function eliminar_todos_los_alias() {}
    /**
     * Libera recursos adicionales del iterador.
     *
     * Placeholder: será implementado en versiones futuras.
     *
     * @since 1.0
     * @version 1.5i.1
     * @return void
     */
  /*  protected function liberar() {
        // Pendiente de implementación
    }*/
    
}