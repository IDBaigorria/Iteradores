<?php
/**
 * Reloj Astronómico — Iteradores Neuronales.
 *
 * Implementa {@link ProveedorVectorGravitacional} y proporciona un
 * **ramillete de espines** (vectores individuales por astro) que representan
 * de forma determinista la configuración del cielo local para cualquier
 * ubicación geográfica e instante de tiempo.
 *
 * A partir de la versión 1.5.1, el reloj abandona el vector único combinado
 * en favor de un conjunto independiente de espines gravitacionales. Cada astro
 * es una entrada autónoma en el Plano Cósmico, con su propia masa y dirección.
 * Esto allana el camino para la arquitectura de *Iteradores Neuronales*, donde
 * el contexto se organiza en planos ortogonales (Cósmico, Rítmico,
 * Estado×Acción).
 *
 * El cálculo utiliza un modelo geométrico simplificado con órbitas circulares
 * que genera ciclos día/noche, fases lunares, estaciones, la precesión nodal
 * lunar (18.6 años), el ciclo de Júpiter (~11.86 años) y el bamboleo del eje
 * terrestre (~25 800 años), sin necesidad de efemérides de alta precisión.
 *
 * ## Prisma geográfico simplificado (v1.5.1)
 *
 * Desde la versión 1.5.1, el prisma geográfico no es un operador externo
 * que transforme cada plano. Es un **espin adicional** en el ramillete:
 * `centro_tierra`, un vector que apunta desde el observador hacia el centro
 * de la Tierra, expresado en el marco inercial del sistema. Al sumarse
 * ponderadamente con los demás espines, distorsiona naturalmente el vector
 * de activación según la ubicación geográfica y la hora del día (vía LST).
 *
 * ## Rol en el sistema
 *
 * - Los iteradores obtienen el ramillete de espines a través del Controlador.
 * - Cada espin se usa para "marcar" las ramas que recorren (huella temporal).
 * - La distancia entre el espin almacenado y el espin actual mide la
 *   "antigüedad" relativa de ese recuerdo.
 * - Permite realizar predicciones buscando ramas cuyos espines sean cercanos
 *   a una configuración futura simulada.
 *
 * @package Iteradores\Tiempo
 * @author Ignacio David Baigorria
 * @since 1.3.5
 * @version 1.5.1
 * @implements ProveedorVectorGravitacional
 */

namespace Iteradores\Tiempo;

use Iteradores\Configuracion\Conf;
use Iteradores\Tiempo\interfaces\ProveedorVectorGravitacional;
use Iteradores\Nucleo\Objeto;

require_once("interfaces\ProveedorVectorGravitacional.php");

class RelojAstronomico extends Objeto implements ProveedorVectorGravitacional
{
    // ═══════════════════════════════════════════════════════
    // REGISTRO DE ASTROS (extensible)
    // ═══════════════════════════════════════════════════════

    /**
     * Astros registrados en el reloj con sus masas gravitacionales.
     *
     * Esta estructura permite agregar nuevos astros sin modificar la lógica
     * de cálculo central. Cada astro es una entrada independiente en el
     * Plano Cósmico.
     *
     * `centro_tierra` actúa como prisma geográfico: es el vector que apunta
     * desde el observador hacia el centro del planeta. Su dirección depende
     * de la latitud, longitud y el tiempo sidéreo local, de modo que dos
     * observadores en distintos lugares (o el mismo lugar en distintos
     * momentos) generan vectores de activación distintos.
     *
     * @var array<string, array{masa: float, tipo: string}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private const ASTROS = [
        'sol'           => ['masa' => 10.0, 'tipo' => 'Astro'],
        'luna'          => ['masa' => 5.8,  'tipo' => 'Astro'],
        'jupiter'       => ['masa' => 7.3,  'tipo' => 'Astro'],
        'eje_terrestre' => ['masa' => 8.0,  'tipo' => 'Eje'],
        'centro_tierra' => ['masa' => 9.0,  'tipo' => 'Prisma'],
    ];

    /**
     * Período orbital de Júpiter en años terrestres.
     *
     * @var float
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PERIODO_JUPITER_ANIOS = 11.86;

    /**
     * Período de precesión del eje terrestre en años terrestres.
     *
     * @var float
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PERIODO_PRECESION_ANIOS = 25800.0;

    // ═══════════════════════════════════════════════════════
    // ESTADO INTERNO PARA CACHÉ DE CÓMPUTOS
    // ═══════════════════════════════════════════════════════

    /**
     * Latitud configurada para esta instancia (en grados, -90 a 90).
     * @var float
     */
    private float $latitud;

    /**
     * Longitud configurada para esta instancia (en grados, -180 a 180).
     * @var float
     */
    private float $longitud;

    /**
     * Último timestamp para el que se calculó el vector (Unix).
     * Se usa para la caché de instancia. `null` si no se ha calculado ninguno.
     * @var int|null
     */
    private ?int $ultimo_timestamp = null;

    /**
     * Último vector de activación calculado (caché).
     * @var array{x: float, y: float, z: float}|null
     */
    private ?array $ultimo_vector = null;

    /**
     * Último ramillete de espines calculado (caché).
     *
     * @var array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>|null
     */
    private ?array $ultimo_espines = null;

    /**
     * Construye un reloj con estado ligado a una ubicación fija.
     *
     * @param float $latitud  Latitud en grados (-90 a 90).
     * @param float $longitud Longitud en grados (-180 a 180).
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    public function __construct(float $latitud, float $longitud)
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS PÚBLICOS (Interfaz ProveedorVectorGravitacional)
    // ═══════════════════════════════════════════════════════

    /**
     * Devuelve el vector de activación combinado para el instante dado.
     *
     * Este método se mantiene por compatibilidad. Internamente construye el
     * vector a partir del ramillete de espines individuales, sumando
     * ponderadamente por masa gravitacional.
     *
     * @param int|null $timestamp Marca de tiempo Unix. Si es null, usa time().
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    public function vector(?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();

        if ($this->ultimo_timestamp === $ts && $this->ultimo_vector !== null) {
            return $this->ultimo_vector;
        }

        $this->ultimo_timestamp = $ts;
        $this->ultimo_vector = self::calcular_vector($this->latitud, $this->longitud, $ts);
        $this->ultimo_espines = null;

        return $this->ultimo_vector;
    }

    /**
     * Método estático para obtener el vector de activación sin estado.
     *
     * @param float    $latitud   Latitud en grados.
     * @param float    $longitud  Longitud en grados.
     * @param int|null $timestamp Marca de tiempo Unix.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    public static function vector_gravitacional(
        float $latitud,
        float $longitud,
        ?int $timestamp = null
    ): array {
        $ts = $timestamp ?? time();
        return self::calcular_vector($latitud, $longitud, $ts);
    }

    /**
     * Actualiza la ubicación geográfica del reloj.
     *
     * Invalida la caché interna para que el próximo cálculo use las nuevas
     * coordenadas.
     *
     * @param float $latitud  Nueva latitud en grados (-90 a 90).
     * @param float $longitud Nueva longitud en grados (-180 a 180).
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    public function _ubicacion(float $latitud, float $longitud): void
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
        $this->ultimo_timestamp = null;
        $this->ultimo_vector = null;
        $this->ultimo_espines = null;
    }

    // ═══════════════════════════════════════════════════════
    // RAMILLETE DE ESPINES (nuevo en 1.5.1)
    // ═══════════════════════════════════════════════════════

    /**
     * Devuelve el ramillete de espines para todos los astros registrados.
     *
     * Cada espin es un array asociativo con:
     * - `nombre`: identificador del astro (ej: 'sol', 'luna', 'jupiter',
     *   'eje_terrestre', 'centro_tierra').
     * - `tipo`: categoría (ej: 'Astro', 'Eje', 'Prisma').
     * - `masa`: masa gravitacional del astro.
     * - `vector`: vector unitario en el marco de referencia inercial
     *   (x, y, z).
     *
     * El espin `centro_tierra` representa la orientación geográfica local:
     * el vector que apunta desde el observador hacia el centro de la Tierra.
     * Su dirección depende de la latitud, longitud y el tiempo sidéreo local
     * (LST), de modo que dos observadores en distintos lugares generan
     * vectores de activación distintos. Esto reemplaza al prisma geográfico
     * complejo de versiones anteriores.
     *
     * @param int|null $timestamp Marca de tiempo Unix.
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function espines(?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();

        if ($this->ultimo_timestamp === $ts && $this->ultimo_espines !== null) {
            return $this->ultimo_espines;
        }

        $this->ultimo_timestamp = $ts;
        $this->ultimo_espines = self::calcular_espines($this->latitud, $this->longitud, $ts);
        $this->ultimo_vector = null;

        return $this->ultimo_espines;
    }

    /**
     * Devuelve el espin de un astro específico.
     *
     * @param string   $astro     Nombre del astro ('sol', 'luna', 'jupiter',
     *                            'eje_terrestre', 'centro_tierra').
     * @param int|null $timestamp Marca de tiempo Unix.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}|null
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function espin(string $astro, ?int $timestamp = null): ?array
    {
        $ts = $timestamp ?? time();

        if (!isset(self::ASTROS[$astro])) {
            self::_error("Astro '{$astro}' no está registrado en el reloj.");
            return null;
        }

        $lat_rad = deg2rad($this->latitud);
        $lon_rad = deg2rad($this->longitud);
        $lst = self::tiempo_sidereo_local($ts, $lon_rad);
        $vector = self::calcular_espin_astro($astro, $ts, $lat_rad, $lon_rad, $lst);

        return [
            'nombre' => $astro,
            'tipo'   => self::ASTROS[$astro]['tipo'],
            'masa'   => self::ASTROS[$astro]['masa'],
            'vector' => $vector,
        ];
    }

    /**
     * Calcula el vector de activación a partir del ramillete de espines.
     *
     * El vector de activación es la suma ponderada por masa de todos los
     * vectores individuales, normalizada a unitario. Este es el vector que
     * alimenta el sistema de ventanas en Iteradores Neuronales.
     *
     * @param int|null $timestamp Marca de tiempo Unix.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function vector_activacion(?int $timestamp = null): array
    {
        $ts = $timestamp ?? time();
        $espines = self::calcular_espines($this->latitud, $this->longitud, $ts);
        return self::activacion_desde_espines($espines);
    }

    // ═══════════════════════════════════════════════════════
    // CÁLCULOS INTERNOS
    // ═══════════════════════════════════════════════════════

    /**
     * Calcula el vector de activación combinado para una ubicación e instante.
     *
     * @param float $latitud  Latitud en grados.
     * @param float $longitud Longitud en grados.
     * @param int   $ts       Timestamp Unix.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    private static function calcular_vector(float $latitud, float $longitud, int $ts): array
    {
        $espines = self::calcular_espines($latitud, $longitud, $ts);
        return self::activacion_desde_espines($espines);
    }

    /**
     * Calcula el ramillete de espines para todos los astros registrados.
     *
     * @param float $latitud  Latitud en grados.
     * @param float $longitud Longitud en grados.
     * @param int   $ts       Timestamp Unix.
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_espines(float $latitud, float $longitud, int $ts): array
    {
        $lat_rad = deg2rad($latitud);
        $lon_rad = deg2rad($longitud);
        $lst = self::tiempo_sidereo_local($ts, $lon_rad);

        $espines = [];
        foreach (self::ASTROS as $nombre => $config) {
            $vector = self::calcular_espin_astro($nombre, $ts, $lat_rad, $lon_rad, $lst);
            $espines[] = [
                'nombre' => $nombre,
                'tipo'   => $config['tipo'],
                'masa'   => $config['masa'],
                'vector' => $vector,
            ];
        }

        return $espines;
    }

    /**
     * Calcula el vector unitario de un astro específico.
     *
     * @param string $nombre  Identificador del astro.
     * @param int    $ts      Timestamp Unix.
     * @param float  $lat_rad Latitud en radianes.
     * @param float  $lon_rad Longitud en radianes.
     * @param float  $lst     Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_espin_astro(
        string $nombre,
        int $ts,
        float $lat_rad,
        float $lon_rad,
        float $lst
    ): array {
        switch ($nombre) {
            case 'sol':
                return self::calcular_vector_sol($ts, $lat_rad, $lst);
            case 'luna':
                return self::calcular_vector_luna($ts, $lat_rad, $lst);
            case 'jupiter':
                return self::calcular_vector_jupiter($ts, $lat_rad, $lst);
            case 'eje_terrestre':
                return self::calcular_vector_eje_terrestre($ts, $lat_rad);
            case 'centro_tierra':
                return self::calcular_vector_centro_tierra($lat_rad, $lon_rad, $lst);
            default:
                self::_error("Astro '{$nombre}' no implementado.");
                return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }
    }

    /**
     * Calcula el vector de activación a partir de un ramillete de espines.
     *
     * @param array $espines Ramillete de espines.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function activacion_desde_espines(array $espines): array
    {
        $x = 0.0;
        $y = 0.0;
        $z = 0.0;

        foreach ($espines as $espin) {
            $m = $espin['masa'];
            $v = $espin['vector'];
            $x += $m * $v['x'];
            $y += $m * $v['y'];
            $z += $m * $v['z'];
        }

        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }

    /**
     * Calcula el Tiempo Sidéreo Local (LST) en radianes.
     *
     * El LST es el ángulo horario del punto vernal para un observador en una
     * longitud dada. Se utiliza para convertir coordenadas ecuatoriales en
     * coordenadas horizontales locales.
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lon_rad Longitud del observador en radianes.
     * @return float LST en radianes (0 a 2π).
     * @author Ignacio David Baigorria
     * @since 1.3.5
     * @version 1.5.1
     */
    private static function tiempo_sidereo_local(int $ts, float $lon_rad): float
    {
        $dias_desde_j2000 = ($ts / Conf::RELOJ_SEGUNDOS_POR_DIA) - 10957.5;
        $gmst_deg = fmod(280.46061837 + 360.98564736629 * $dias_desde_j2000, 360.0);
        if ($gmst_deg < 0) {
            $gmst_deg += 360.0;
        }
        $gmst_rad = deg2rad($gmst_deg);

        $lst = fmod($gmst_rad + $lon_rad, 2.0 * M_PI);
        return $lst < 0 ? $lst + 2.0 * M_PI : $lst;
    }

    /**
     * Calcula el vector unitario del Sol.
     *
     * Modelo simplificado: órbita circular, año juliano.
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lat_rad Latitud en radianes.
     * @param float $lst     Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_vector_sol(int $ts, float $lat_rad, float $lst): array
    {
        $angulo_anual = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_ANIO) / Conf::RELOJ_SEGUNDOS_POR_ANIO;
        $ar = fmod($angulo_anual, 2.0 * M_PI);
        $declinacion = deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA) * sin($angulo_anual);

        return self::vector_horizontal($ar, $declinacion, $lat_rad, $lst);
    }

    /**
     * Calcula el vector unitario de la Luna.
     *
     * Modelo simplificado: período sinódico (~29.53 días) + precesión nodal
     * (18.6 años).
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lat_rad Latitud en radianes.
     * @param float $lst     Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_vector_luna(int $ts, float $lat_rad, float $lst): array
    {
        $angulo_sinodico = 2.0 * M_PI * fmod($ts, Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO) / Conf::RELOJ_SEGUNDOS_POR_MES_SINODICO;
        $angulo_nodal = 2.0 * M_PI * fmod($ts, Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO)
            / (Conf::RELOJ_PERIODO_PRECESION_NODAL * Conf::RELOJ_SEGUNDOS_POR_ANIO);

        $longitud_ecliptica = $angulo_sinodico;
        $declinacion_max = deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA + Conf::RELOJ_INCLINACION_LUNAR);
        $declinacion = $declinacion_max * sin($longitud_ecliptica) * cos($angulo_nodal);
        $ar = $longitud_ecliptica;

        return self::vector_horizontal($ar, $declinacion, $lat_rad, $lst);
    }

    /**
     * Calcula el vector unitario de Júpiter.
     *
     * Modelo simplificado: órbita circular con período de ~11.86 años.
     * Júpiter se modela como un astro en la eclíptica que avanza lentamente
     * por el cielo, sirviendo como marcador de época.
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lat_rad Latitud en radianes.
     * @param float $lst     Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_vector_jupiter(int $ts, float $lat_rad, float $lst): array
    {
        $periodo = Conf::RELOJ_SEGUNDOS_POR_ANIO * self::PERIODO_JUPITER_ANIOS;
        $angulo = 2.0 * M_PI * fmod($ts, $periodo) / $periodo;
        $ar = fmod($angulo, 2.0 * M_PI);
        $declinacion = deg2rad(Conf::RELOJ_INCLINACION_ECLIPTICA) * sin($angulo);

        return self::vector_horizontal($ar, $declinacion, $lat_rad, $lst);
    }

    /**
     * Calcula el vector unitario del eje terrestre.
     *
     * Modelo simplificado del bamboleo (precesión). El eje terrestre se
     * representa como un vector que gira lentamente alrededor de la vertical
     * local con un período de ~25 800 años. En el instante actual apunta
     * aproximadamente hacia el polo norte geográfico; dentro de 12 900 años
     * apuntará hacia el sur.
     *
     * Esto permite que el sistema distinga eras separadas por milenios,
     * asignando distancias máximas a momentos cuyos ejes son opuestos.
     *
     * @param int   $ts      Timestamp Unix.
     * @param float $lat_rad Latitud en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_vector_eje_terrestre(int $ts, float $lat_rad): array
    {
        $periodo = Conf::RELOJ_SEGUNDOS_POR_ANIO * self::PERIODO_PRECESION_ANIOS;
        $theta = 2.0 * M_PI * fmod($ts, $periodo) / $periodo;

        $x = sin($theta) * cos($lat_rad);
        $y = cos($theta) * cos($lat_rad);
        $z = sin($lat_rad);

        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }

    /**
     * Calcula el vector unitario del centro de la Tierra (prisma geográfico).
     *
     * Este espin representa la orientación geográfica local del observador.
     * Es el vector que apunta desde el observador hacia el centro de la
     * Tierra, expresado en el marco inercial del sistema.
     *
     * En el modelo simplificado, asumimos que el eje de rotación terrestre
     * está alineado con el eje Z del marco inercial. Entonces el vector
     * "abajo" del observador depende de su latitud y de su longitud efectiva
     * (longitud + LST):
     *
     * $$\hat{u}_{centro} = (-\cos\phi \cos\theta, \; -\cos\phi \sin\theta, \; -\sin\phi)$$
     *
     * donde $\phi$ es la latitud y $\theta = \lambda + \text{LST}$ es la
     * longitud efectiva en el marco inercial.
     *
     * Al sumarse ponderadamente con los demás espines, este vector distorsiona
     * naturalmente el vector de activación según la ubicación geográfica,
     * eliminando la necesidad de un operador prisma externo.
     *
     * @param float $lat_rad Latitud en radianes.
     * @param float $lon_rad Longitud en radianes.
     * @param float $lst     Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_vector_centro_tierra(float $lat_rad, float $lon_rad, float $lst): array
    {
        $theta = $lon_rad + $lst;

        $x = -cos($lat_rad) * cos($theta);
        $y = -cos($lat_rad) * sin($theta);
        $z = -sin($lat_rad);

        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => -1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }

    /**
     * Convierte coordenadas ecuatoriales a horizontales locales.
     *
     * El vector resultante está orientado de modo que:
     * - `x` apunta hacia el Este.
     * - `y` apunta hacia el Norte.
     * - `z` apunta hacia el Cenit (arriba).
     *
     * @param float $ar          Ascensión recta (o ángulo horario equivalente).
     * @param float $declinacion Declinación en radianes.
     * @param float $lat_rad     Latitud en radianes.
     * @param float $lst         Tiempo Sidéreo Local en radianes.
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function vector_horizontal(float $ar, float $declinacion, float $lat_rad, float $lst): array
    {
        $angulo_horario = fmod($lst - $ar, 2.0 * M_PI);
        if ($angulo_horario < 0) {
            $angulo_horario += 2.0 * M_PI;
        }

        $sin_alt = sin($declinacion) * sin($lat_rad)
                 + cos($declinacion) * cos($lat_rad) * cos($angulo_horario);
        $altitud = asin(max(-1.0, min(1.0, $sin_alt)));

        $cos_alt = cos($altitud);
        if (abs($cos_alt) < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => $sin_alt > 0 ? 1.0 : -1.0];
        }

        $sin_az = -cos($declinacion) * sin($angulo_horario) / $cos_alt;
        $cos_az = (sin($declinacion) - sin($lat_rad) * sin($altitud)) / (cos($lat_rad) * $cos_alt);
        $azimut = atan2($sin_az, $cos_az);

        $x = cos($altitud) * sin($azimut);
        $y = cos($altitud) * cos($azimut);
        $z = sin($altitud);

        $magnitud = sqrt($x * $x + $y * $y + $z * $z);
        if ($magnitud < 1e-9) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 1.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }
}
