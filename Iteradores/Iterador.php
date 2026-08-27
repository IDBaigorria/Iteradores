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
            return false;
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
            return false;
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

    //placeholder
        /**
     * Devuelve el nodo de alias permitidos para la clase.
     * Placeholder: debe ser implementado por subclases si se requiere.
     *
     * @since 1.0
     * @version 1.5i.0
     * @return Nodo|null
     */
    protected function _alias_permitidos() { return Nodo::nodo("hola"); }

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
    protected function eliminar_todos_los_alias() {}
}