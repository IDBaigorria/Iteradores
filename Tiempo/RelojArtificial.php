<?php

/**
 * RelojArtificial - Plano Rítmico ($\mathcal{R}$)
 * 
 * Ciclos culturales, artificiales y descubiertos. Espejo del RelojAstronomico
 * pero para ritmos humanos. Cada ciclo vive en $S^1$ (círculo unitario 2D).
 * 
 * Incluye Prisma Geográfico Rítmico: un espin adicional que usa la longitud
 * efectiva (longitud + LST) para distorsionar el plano rítmico según la
 * ubicación geográfica, permitiendo descubrir zonas horarias.
 * 
 * @author Ignacio David Baigorria
 * @since 1.0.0
 * @version 1.5.1
 */
class RelojArtificial
{
    /**
     * Ciclos registrados.
     * @var array<string, array>
     * @since 1.0.0
     * @version 1.5.1
     */
    private array $ciclos = [];

    /**
     * Latitud del observador.
     * @var float
     * @since 1.5.1
     * @version 1.5.1
     */
    private float $latitud;

    /**
     * Longitud del observador.
     * @var float
     * @since 1.5.1
     * @version 1.5.1
     */
    private float $longitud;

    /**
     * Caché de espines.
     * @var array|null
     * @since 1.0.0
     * @version 1.5.1
     */
    private ?array $_cache_espines = null;

    /**
     * Timestamp de la caché.
     * @var int|null
     * @since 1.0.0
     * @version 1.5.1
     */
    private ?int $_cache_ts = null;

    /**
     * LST de la caché.
     * @var float|null
     * @since 1.5.1
     * @version 1.5.1
     */
    private ?float $_cache_lst = null;

    /**
     * Constructor.
     * 
     * @param float $latitud Latitud del observador.
     * @param float $longitud Longitud del observador.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function __construct(float $latitud = 0.0, float $longitud = 0.0)
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
        $this->inicializar_ciclos_fabrica();
    }

    /**
     * Inicializa los ciclos de fábrica.
     * 
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    private function inicializar_ciclos_fabrica(): void
    {
        $this->ciclos = [
            'dia_noche' => ['periodo' => 86400.0, 'fase' => 0.0, 'masa' => 6.0, 'tipo' => 'Ritmo'],
            'semana'    => ['periodo' => 604800.0, 'fase' => 0.0, 'masa' => 4.5, 'tipo' => 'Ritmo'],
            'anno'      => ['periodo' => 31557600.0, 'fase' => 0.0, 'masa' => 5.0, 'tipo' => 'Ritmo'],
            'hora'      => ['periodo' => 3600.0, 'fase' => 0.0, 'masa' => 5.5, 'tipo' => 'Ritmo'],
            'minuto'    => ['periodo' => 60.0, 'fase' => 0.0, 'masa' => 3.0, 'tipo' => 'Ritmo'],
        ];
    }

    /**
     * Agrega un ciclo nuevo.
     * 
     * @param string $nombre Nombre del ciclo.
     * @param float $periodo Período en segundos.
     * @param float $fase Fase inicial en radianes.
     * @param float $masa Masa del ciclo.
     * @param string $tipo Tipo del ciclo.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function agregar_ciclo(string $nombre, float $periodo, float $fase, float $masa, string $tipo = 'Ritmo'): void
    {
        $this->ciclos[$nombre] = [
            'periodo' => $periodo,
            'fase'    => $fase,
            'masa'    => $masa,
            'tipo'    => $tipo,
        ];
        $this->invalidar_cache();
    }

    /**
     * Elimina un ciclo.
     * 
     * @param string $nombre Nombre del ciclo.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function eliminar_ciclo(string $nombre): void
    {
        unset($this->ciclos[$nombre]);
        $this->invalidar_cache();
    }

    /**
     * Obtiene la configuración de un ciclo.
     * 
     * @param string $nombre Nombre del ciclo.
     * @return array|null Configuración o null.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function ciclo(string $nombre): ?array
    {
        return $this->ciclos[$nombre] ?? null;
    }

    /**
     * Devuelve todos los ciclos registrados.
     * 
     * @return array<string, array>
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function ciclos_registrados(): array
    {
        return $this->ciclos;
    }

    /**
     * Descubre un ciclo a partir de un período estimado.
     * 
     * @param string $nombre Nombre del ciclo.
     * @param float $periodo Período estimado en segundos.
     * @param float $ts_ref Timestamp de referencia.
     * @param float $masa_inicial Masa inicial.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function descubrir_ciclo(string $nombre, float $periodo, float $ts_ref, float $masa_inicial = 1.0): void
    {
        $fase = fmod($ts_ref, $periodo) / $periodo * 2.0 * M_PI;
        $this->agregar_ciclo($nombre, $periodo, $fase, $masa_inicial, 'RitmoDescubierto');
    }

    /**
     * Calcula el espin de un ciclo.
     * 
     * @param string $nombre Nombre del ciclo.
     * @param int $timestamp Timestamp.
     * @return array Espin del ciclo.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    private function calcular_espin_ciclo(string $nombre, int $timestamp): array
    {
        $config = $this->ciclos[$nombre];
        $periodo = $config['periodo'];
        $fase = $config['fase'];
        $masa = $config['masa'];
        $tipo = $config['tipo'];

        $angulo = $fase + (fmod($timestamp, $periodo) / $periodo) * 2.0 * M_PI;

        return [
            'nombre' => $nombre,
            'tipo'   => $tipo,
            'masa'   => $masa,
            'vector' => [
                'x' => cos($angulo),
                'y' => sin($angulo),
                'z' => 0.0,
            ],
        ];
    }

    /**
     * Calcula el espin prisma rítmico geográfico.
     * 
     * Usa la longitud efectiva (longitud + LST) para situar al observador
     * en el círculo rítmico, permitiendo distorsión geográfica del plano.
     * 
     * @param float $lst Tiempo sidéreo local en radianes.
     * @return array Espin prisma.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function calcular_espin_prisma(float $lst): array
    {
        $theta = deg2rad($this->longitud) + $lst;

        return [
            'nombre' => 'prisma_ritmico',
            'tipo'   => 'Prisma',
            'masa'   => 4.0,
            'vector' => [
                'x' => cos($theta),
                'y' => sin($theta),
                'z' => 0.0,
            ],
        ];
    }

    /**
     * Devuelve el ramillete de espines rítmicos.
     * 
     * @param int|null $timestamp Timestamp. Si es null, usa time().
     * @param float|null $lst Tiempo sidéreo local en radianes. Si se proporciona, incluye el prisma rítmico.
     * @return array Ramillete de espines.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function espines(?int $timestamp = null, ?float $lst = null): array
    {
        $ts = $timestamp ?? time();

        if ($this->_cache_espines !== null && $this->_cache_ts === $ts && $this->_cache_lst === $lst) {
            return $this->_cache_espines;
        }

        $espines = [];
        foreach ($this->ciclos as $nombre => $config) {
            $espines[] = $this->calcular_espin_ciclo($nombre, $ts);
        }

        if ($lst !== null) {
            $espines[] = $this->calcular_espin_prisma($lst);
        }

        $this->_cache_espines = $espines;
        $this->_cache_ts = $ts;
        $this->_cache_lst = $lst;

        return $espines;
    }

    /**
     * Devuelve un espin específico.
     * 
     * @param string $nombre Nombre del espin.
     * @param int|null $timestamp Timestamp.
     * @param float|null $lst LST en radianes.
     * @return array|null Espin o null.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function espin(string $nombre, ?int $timestamp = null, ?float $lst = null): ?array
    {
        if ($nombre === 'prisma_ritmico') {
            if ($lst === null) {
                return null;
            }
            return $this->calcular_espin_prisma($lst);
        }

        if (!isset($this->ciclos[$nombre])) {
            return null;
        }

        $ts = $timestamp ?? time();
        return $this->calcular_espin_ciclo($nombre, $ts);
    }

    /**
     * Calcula el vector de activación combinado.
     * 
     * @param int|null $timestamp Timestamp.
     * @param float|null $lst LST en radianes.
     * @return array Vector {x, y, z}.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function vector_activacion(?int $timestamp = null, ?float $lst = null): array
    {
        $espines = $this->espines($timestamp, $lst);

        $sx = 0.0;
        $sy = 0.0;
        $sz = 0.0;
        $masa_total = 0.0;

        foreach ($espines as $espin) {
            $m = $espin['masa'];
            $sx += $m * $espin['vector']['x'];
            $sy += $m * $espin['vector']['y'];
            $sz += $m * $espin['vector']['z'];
            $masa_total += $m;
        }

        if ($masa_total < 1e-12) {
            return ['x' => 0.0, 'y' => 0.0, 'z' => 0.0];
        }

        return [
            'x' => $sx / $masa_total,
            'y' => $sy / $masa_total,
            'z' => $sz / $masa_total,
        ];
    }

    /**
     * Alias de vector_activacion.
     * 
     * @param int|null $timestamp Timestamp.
     * @param float|null $lst LST en radianes.
     * @return array Vector {x, y, z}.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function vector(?int $timestamp = null, ?float $lst = null): array
    {
        return $this->vector_activacion($timestamp, $lst);
    }

    /**
     * Invalida la caché interna.
     * 
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    private function invalidar_cache(): void
    {
        $this->_cache_espines = null;
        $this->_cache_ts = null;
        $this->_cache_lst = null;
    }
}
