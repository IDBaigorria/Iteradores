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
    
    /**
     * Tiempo base de un ciclo de simulación (en segundos).
     * Se usa para calcular la fuga de energía proporcional al tiempo real.
     * @var float
     */
    public const TIEMPO_CICLO = 1.0;    

    // ═══════════════════════════════════════════════════════════
    // APARIENCIA DE BLOQUES DE DEPURACIÓN
    // ═══════════════════════════════════════════════════════════

    /**
     * Colores de fondo, texto y borde para el bloque de errores.
     *
     * @var array{ fondo: string, texto: string, borde: string }
     */
    public const ERRORES_COLORES = [
        'fondo' => '#fee',
        'texto' => '#900',
        'borde' => '#c00',
        'ansi_fondo' => '41',   // rojo
        'ansi_texto' => '31',   // rojo
    ];

    /**
     * Colores para el bloque de alertas.
     *
     * @var array{ fondo: string, texto: string, borde: string }
     */
    public const ALERTAS_COLORES = [
        'fondo' => '#fffde7',
        'texto' => '#864100',
        'borde' => '#ffc107',
        'ansi_fondo' => '43',   // amarillo
        'ansi_texto' => '33',
    ];

    /**
     * Colores para el bloque de impresión de nodos.
     *
     * @var array{ fondo: string, texto: string, borde: string }
     */
    public const NODOS_COLORES = [
        'fondo' => '#eef6ff',
        'texto' => '#003366',
        'borde' => '#0066cc',
        'ansi_fondo' => '44',   // azul
        'ansi_texto' => '34',
    ];
    // ═══════════════════════════════════════════════════════════
    // RELOJ ASTRONÓMICO
    // ═══════════════════════════════════════════════════════════

    /**
     * Peso del vector solar en la combinación final del Reloj Astronómico.
     *
     * Determina la influencia relativa del Sol frente a la Luna en el vector
     * gravitacional resultante. Un valor mayor da más peso al ciclo día/noche.
     *
     * @var float
     * @see \Iteradores\Tiempo\RelojAstronomico
     * @since 1.3.5
     */
    public const RELOJ_ALFA_SOL = 0.7;

    /**
     * Peso del vector lunar en la combinación final del Reloj Astronómico.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_BETA_LUNA = 0.3;

    /**
     * Inclinación de la eclíptica respecto al ecuador celeste, en grados.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_INCLINACION_ECLIPTICA = 23.5;

    /**
     * Inclinación de la órbita lunar respecto a la eclíptica, en grados.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_INCLINACION_LUNAR = 5.15;

    /**
     * Período de precesión del nodo ascendente lunar, en años.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_PERIODO_PRECESION_NODAL = 18.6;

    /**
     * Radio medio de la Tierra en metros (reservado para uso futuro).
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_RADIO_TIERRA = 6371000.0;

    /**
     * Duración de un día solar medio, en segundos.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_SEGUNDOS_POR_DIA = 86400.0;

    /**
     * Duración de un año juliano (365.25 días), en segundos.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_SEGUNDOS_POR_ANIO = 31557600.0;

    /**
     * Duración de un mes sinódico lunar (~29.53 días), en segundos.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_SEGUNDOS_POR_MES_SINODICO = 2551442.8;

    /**
     * Duración de un día sidéreo (23h 56m 4s), en segundos.
     *
     * @var float
     * @since 1.3.5
     */
    public const RELOJ_SEGUNDOS_POR_DIA_SIDEREO = 86164.0905;
    
    // ═══════════════════════════════════════════════════════════
    // UBICACIÓN GEOGRÁFICA
    // ═══════════════════════════════════════════════════════════

    /**
     * Latitud predeterminada cuando no se puede detectar la ubicación real.
     *
     * Utilizada por {@link \Iteradores\Configuracion\Entorno::obtener_coordenadas()}
     * como último recurso (fallback).
     *
     * @var float
     * @since 1.3.6
     */
    public const LATITUD_PREDETERMINADA = -34.0;   //Tres Arroyos, Argentina

    /**
     * Longitud predeterminada cuando no se puede detectar la ubicación real.
     *
     * @var float
     * @since 1.3.6
     */
    public const LONGITUD_PREDETERMINADA = -64.0;

    /**
     * URL del servicio de geolocalización por IP.
     *
     * Se utiliza en {@link \Iteradores\Configuracion\Entorno::obtener_coordenadas()}
     * cuando no hay coordenadas en sesión. El servicio debe devolver un JSON
     * con las claves "lat" y "lon".
     *
     * Valor por defecto: freegeoip.app (uso comercial permitido, sin API key).
     * @var string
     * @since 1.3.6
     */
    public const GEOLOCALIZACION_URL = 'https://freegeoip.app/json/';

    // ═══════════════════════════════════════════════════════════
    // MOTOR DE EJECUCIÓN (v1.3.7)
    // ═══════════════════════════════════════════════════════════
    /**
     * Número máximo de ciclos que ejecuta el motor antes de detenerse.
     *
     * Un valor de 0 significa "sin límite" (bucle infinito, típico en CLI).
     * En pruebas, se puede poner un número pequeño (ej. 1 o 2) para verificar
     * el funcionamiento sin colgar el proceso.
     *
     * @var int
     * @since 1.3.7
     */
    public const MOTOR_MAX_CICLOS = 2; //0=infinito

    /**
     * Frecuencia del motor en ciclos por minuto.
     *
     * Determina cuántas veces por minuto el motor ejecuta una rodaja de trabajo.
     * Es la configuración primaria de la que se deriva {@link MOTOR_INTERVALO_MS}.
     * Un valor de 20 equivale a un ciclo cada 3 segundos.
     *
     * @var int
     * @since 1.3.7
     */
    public const MOTOR_CICLOS_POR_MINUTO = 20;

    /**
     * Intervalo en milisegundos entre ciclos del motor.
     *
     * Se calcula automáticamente como `60000 / MOTOR_CICLOS_POR_MINUTO`.
     * Con el valor por defecto (20), resulta en 3000 ms.
     *
     * @var int
     * @since 1.3.7
     */
    public const MOTOR_INTERVALO_MS = 60000 / self::MOTOR_CICLOS_POR_MINUTO;

    /**
     * Número máximo de comandos que se ejecutan en un solo ciclo del motor.
     *
     * Controla la duración de cada rodaja de trabajo. Un valor más alto
     * reduce la reactividad pero aumenta el rendimiento.
     *
     * @var int
     * @since 1.3.7
     */
    public const MOTOR_QUANTUM = 20;

    /**
     * Tiempo máximo en segundos que el motor espera durante una pausa urgente
     * antes de reanudarse automáticamente.
     *
     * @var int
     * @since 1.3.7
     */
    public const MOTOR_PAUSA_URGENTE_TIMEOUT_S = 30;

    /**
     * Matriz que actúa como marca de inicio para conjuntos desordenados.
     *
     * Forma: [[1, 1], [0, 1]]
     * 
     * Se utiliza en {@link \Iteradores\Nodos\NodoNumerico::crear_conjunto()}
     * para diferenciar algebraicamente un conjunto de una secuencia.
     *
     * @var array
     * @since 1.4.2
     */
    public const MATRIZ_MARCA_CONJUNTO = [[1, 1], [0, 1]];
    
    // ═══════════════════════════════════════════════════════════
    // PRIMOS PRECARGADOS (v1.4.8)
    // ═══════════════════════════════════════════════════════════

    /**
     * Primeros 512 números primos, precargados para evitar generación bajo demanda.
     *
     * Rangos semánticos (v1.4.8+):
     *   - Índices   0..255  → bytes del Tálamo (mapeo byte↔matriz)
     *   - Índice  256       → NodoPrimo de marcado: comando
     *   - Índice  257       → NodoPrimo de marcado: medio
     *   - Índice  258       → NodoPrimo de marcado: dirección
     *   - Índice  259       → NodoPrimo de marcado: mensaje
     *   - Índices 260..264  → NodoPrimo de acción: aprender, predecir, imaginar, controlar, ejecutar
     *   - Índices 265..511  → reservados para futuros marcadores o acciones
     *
     * @var int[]
     * @since 1.4.8
     */
    public const PRIMOS_PRECARGADOS = [
        // 0..255: bytes del Tálamo
        2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71,
        73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151,
        157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233,
        239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317,
        331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397, 401, 409, 419,
        421, 431, 433, 439, 443, 449, 457, 461, 463, 467, 479, 487, 491, 499, 503,
        509, 521, 523, 541, 547, 557, 563, 569, 571, 577, 587, 593, 599, 601, 607,
        613, 617, 619, 631, 641, 643, 647, 653, 659, 661, 673, 677, 683, 691, 701,
        709, 719, 727, 733, 739, 743, 751, 757, 761, 769, 773, 787, 797, 809, 811,
        821, 823, 827, 829, 839, 853, 857, 859, 863, 877, 881, 883, 887, 907, 911,
        919, 929, 937, 941, 947, 953, 967, 971, 977, 983, 991, 997, 1009, 1013,
        1019, 1021, 1031, 1033, 1039, 1049, 1051, 1061, 1063, 1069, 1087, 1091,
        1093, 1097, 1103, 1109, 1117, 1123, 1129, 1151, 1153, 1163, 1171, 1181,
        1187, 1193, 1201, 1213, 1217, 1223, 1229, 1231, 1237, 1249, 1259, 1277,
        1279, 1283, 1289, 1291, 1297, 1301, 1303, 1307, 1319, 1321, 1327, 1361,
        1367, 1373, 1381, 1399, 1409, 1423, 1427, 1429, 1433, 1439, 1447, 1451,
        1453, 1459, 1471, 1481, 1483, 1487, 1489, 1493, 1499, 1511, 1523, 1531,
        1543, 1549, 1553, 1559, 1567, 1571, 1579, 1583, 1597, 1601, 1607, 1609,
        1613, 1619,
        
        // 256..511: mas primos
        1621, 1627, 1637, 1657, 1663, 1667, 1669, 1693, 1697, 
        1699, 1709, 1721, 1723, 1733, 1741, 1747, 1753, 1759, 1777, 1783, 1787,
        1789, 1801, 1811, 1823, 1831, 1847, 1861, 1867, 1871, 1873, 1877, 1879,
        1889, 1901, 1907, 1913, 1931, 1933, 1949, 1951, 1973, 1979, 1987, 1993,
        1997, 1999, 2003, 2011, 2017, 2027, 2029, 2039, 2053, 2063, 2069, 2081,
        2083, 2087, 2089, 2099, 2111, 2113, 2129, 2131, 2137, 2141, 2143, 2153,
        2161, 2179, 2203, 2207, 2213, 2221, 2237, 2239, 2243, 2251, 2267, 2269,
        2273, 2281, 2287, 2293, 2297, 2309, 2311, 2333, 2339, 2341, 2347, 2351,
        2357, 2371, 2377, 2381, 2383, 2389, 2393, 2399, 2411, 2417, 2423, 2437,
        2441, 2447, 2459, 2467, 2473, 2477, 2503, 2521, 2531, 2539, 2543, 2549,
        2551, 2557, 2579, 2591, 2593, 2609, 2617, 2621, 2633, 2647, 2657, 2659,
        2663, 2671, 2677, 2683, 2687, 2689, 2693, 2699, 2707, 2711, 2713, 2719,
        2729, 2731, 2741, 2749, 2753, 2767, 2777, 2789, 2791, 2797, 2801, 2803,
        2819, 2833, 2837, 2843, 2851, 2857, 2861, 2879, 2887, 2897, 2903, 2909,
        2917, 2927, 2939, 2953, 2957, 2963, 2969, 2971, 2999, 3001, 3011, 3019,
        3023, 3037, 3041, 3049, 3061, 3067, 3079, 3083, 3089, 3109, 3119, 3121,
        3137, 3163, 3167, 3169, 3181, 3187, 3191, 3203, 3209, 3217, 3221, 3229,
        3251, 3253, 3257, 3259, 3271, 3299, 3301, 3307, 3313, 3319, 3323, 3329,
        3331, 3343, 3347, 3359, 3361, 3371, 3373, 3389, 3391, 3407, 3413, 3433,
        3449, 3457, 3461, 3463, 3467, 3469, 3491, 3499, 3511, 3517, 3527, 3529,
        3533, 3539, 3541, 3547, 3557, 3559, 3571, 3581, 3583, 3593, 3607, 3613,
        3617, 3623, 3631, 3637, 3643, 3659, 3671,
    ];

        // ═══════════════════════════════════════════════════════════
    // VERBOS DE ACCIÓN (v1.4.9)
    // ═══════════════════════════════════════════════════════════

    /**
     * Verbo de cierre: indica el fin de una comunicación entre Iteradores.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_CIERRE = 0;

    /**
     * Verbo: aprender un nuevo patrón o secuencia.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_APRENDER = 1;

    /**
     * Verbo: ejecutar una acción ya aprendida.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_EJECUTAR = 2;

    /**
     * Verbo: tomar el control de un recurso o iterador.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_CONTROLAR = 3;

    /**
     * Verbo: corregir un patrón o secuencia previamente aprendida.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_CORREGIR = 4;

    /**
     * Verbo: solicitar una predicción basada en patrones conocidos.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_PREDECIR = 5;

    /**
     * Verbo: generar contenido nuevo a partir de lo aprendido.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_IMAGINAR = 6;

    /**
     * Verbo: supervisar el funcionamiento de otro Iterador.
     * @var int
     * @since 1.4.9
     */
    public const VERBO_SUPERVISAR = 7;

    // Se pueden añadir más verbos según sea necesario, idealmente de forma secuencial.
}

?>