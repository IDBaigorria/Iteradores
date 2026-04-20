<?php
namespace Iteradores\Configuracion;
/**
 * v0.0.0.250829 Inicio, creo la clase Conf para ir colocando ahi las constantes globales de configuracion
 * 
 */
class Conf {
    // Sobre la aplicacion
    public const NOMBRE_APP = "MiSuperApp";
    public const VERSION_APP = "0.0.0";
    public const AUTOR_APP = "Ignacio David Baigorria";
    // Sobre las sesiones
    public const PREFIJO_SESSION = self::NOMBRE_APP . "_";
    // Sobre si se ejecuta en el localhost o en hosting de internet
    public const LOCAL = TRUE;
    // Sobre las bases de datos
    // Base de datos general
    /**
     * Dirección del servidor donde se aloja la base de datos principal.
     * 
     * Generalmente es "localhost" cuando la base se encuentra en el mismo servidor.
     * @var string
     */
    public const HOST_SQL = "localhost";

    /**
     * Nombre de usuario utilizado para conectarse a la base de datos principal.
     * 
     * Debe tener permisos de lectura y escritura sobre la base de datos especificada.
     * @var string
     */
    public const USUARIO_SQL = "root";

    /**
     * Contraseña asociada al usuario de la base de datos principal.
     * 
     * Puede ser una cadena vacía en entornos locales sin contraseña.
     * @var string
     */
    public const CONTRASENA_SQL = "";

    /**
     * Nombre de la base de datos principal utilizada por el sistema.
     * 
     * Contiene las tablas para hilos y superestructuras.
     * @var string
     */
    public const NOMBRE_BD_SQL = "HyS";
    
    /**
     * Método predeterminado utilizado para guardar y recuperar la superestructura.
     * 
     * Define la tecnología o formato de persistencia que se empleará por defecto.
     * Los valores posibles pueden ser, por ejemplo: "sql", "json" o "texto_plano".
     * 
     * @var string
     * @default "sql"
     */
    public const SUPERESTRUCTURA_METODO_PERDURAR = "SQL";

    /**
     * Servidor SQL destinado a la persistencia de la superestructura.
     * 
     * Por defecto, utiliza el mismo host que la base de datos principal.
     * @var string
     */
    public const SUPERESTRUCTURA_HOST_SQL = self::HOST_SQL;

    /**
     * Usuario SQL con permisos para operar sobre la base de datos de la superestructura.
     * 
     * Generalmente es el mismo usuario que el de la base de datos principal.
     * @var string
     */
    public const SUPERESTRUCTURA_USUARIO_SQL = self::USUARIO_SQL;

    /**
     * Contraseña correspondiente al usuario SQL de la superestructura.
     * 
     * Puede heredarse de la configuración principal del sistema.
     * @var string
     */
    public const SUPERESTRUCTURA_CONTRASENA_SQL = self::CONTRASENA_SQL;

    /**
     * Nombre de la base de datos SQL utilizada para guardar la superestructura.
     * 
     * Normalmente coincide con la base de datos principal.
     * @var string
     */
    public const SUPERESTRUCTURA_NOMBRE_BD_SQL = self::NOMBRE_BD_SQL;

    /**
     * Carpeta donde se guardarán los archivos de la superestructura en formato JSON.
     * 
     * Puede definirse una ruta absoluta o relativa dentro del proyecto.
     * @var string
     */
    public const SUPERESTRUCTURA_CARPETA_GUARDAR_JSON = "JSON";

    /**
     * Carpeta donde se guardarán los archivos de la superestructura en formato XML.
     * 
     * Puede definirse una ruta absoluta o relativa dentro del proyecto.
     * @var string
     */
    public const SUPERESTRUCTURA_CARPETA_GUARDAR_XML  = "XML";


    //////////////////////////////////////////////////////////////////////////////////////
    //  Sobre los errores y alertas //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Indica si la recolección de errores está activada de forma predeterminada.
     *
     * Esta constante define el estado inicial de la recolección de errores
     * para todos los objetos del sistema. Su valor puede ser sobrescrito
     * dinámicamente mediante los métodos 
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores Objeto::activar_errores()},
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores Objeto::desactivar_errores()},
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas Objeto::activar_errores_y_alertas()} y
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas Objeto::desactivar_errores_y_alertas()}
     *
     * @var bool
     */
    public const ACTIVAR_ERRORES= true;

    /**
     * Indica si la recolección de alertas está activada de forma predeterminada.
     *
     * Esta constante define el estado inicial de la recolección de alertas
     * para todos los objetos del sistema. Su valor puede ser sobrescrito
     * dinámicamente mediante los métodos 
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_alertas Objeto::activar_alertas()},
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_alertas Objeto::desactivar_alertas()},
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_activar_errores_y_alertas Objeto::activar_errores_y_alertas()} y
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method_desactivar_errores_y_alertas Objeto::desactivar_errores_y_alertas()}
     *
     * @var bool
     */
    public const ACTIVAR_ALERTAS = true;
   
    // Sobre los errores y alertas (datos que se recolectan en la pila de llamadas)

    /**
     * Límite máximo de profundidad de la pila de llamadas a almacenar
     * para cada error o alerta recolectada.
     *
     * Limitar la profundidad ayuda a controlar el uso de memoria,
     * ya que cada error o alerta conserva parte de la traza de llamadas
     * que lo originó. Este valor afecta el comportamiento de los métodos
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error Objeto::_error()}
     * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta Objeto::_alerta()}.
     *
     * @var int
     */
    public const ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__LIMITE = 10;
    /**
     * Indica si deben almacenarse los argumentos pasados a cada llamada
     * en la pila de llamadas de los errores y alertas recolectados.
     *
     * Activar esta opción puede incrementar significativamente el uso
     * de memoria, especialmente cuando se registran muchas llamadas con
     * argumentos de gran tamaño. Este valor afecta el comportamiento de los métodos
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error Objeto::_error()}
     * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta Objeto::_alerta()}.
     *
     * @var bool
     */
    public const ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_ARGUMENTOS = true;
    /**
     * Indica si deben almacenarse las referencias a objetos involucrados
     * en cada llamada de la pila de errores y alertas recolectados.
     *
     * Guardar objetos completos puede consumir gran cantidad de memoria,
     * por lo que desactivar esta opción ayuda a reducir el impacto cuando
     * se registran muchas llamadas. Este valor afecta el comportamiento de los métodos
     * {@link ./classes/Iteradores-Nucleo-Objeto.html#method__error Objeto::_error()}
     * y {@link ./classes/Iteradores-Nucleo-Objeto.html#method__alerta Objeto::_alerta()}.
     *
     * @var bool
     */
    public const ERRORES_Y_ALERTAS__PILA_DE_LLAMADAS__INCLUIR_OBJETOS = true;
   // public const 

    /////////////////////////////////////////////////////////////////////////////////////
     //  NodoElectrico ////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////
    /**
     * Capacidad maxima almacenada por defecto. 
     * 
     * Se usa cuando se crean nodos nuevos y no se especifica la capacidad del mismo
     * @var int
     */
    public const CAPACIDAD_NODO_ELECTRICO=256;
    /**
     * Cantidad de energia por defecto que se pierde por ciclo de tiempo
     * @var int
     */
    public const FUGA_NODO_ELECTRICO=0;
    


}

?>