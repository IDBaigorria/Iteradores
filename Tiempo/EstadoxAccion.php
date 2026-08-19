<?php
/**
 * Estado × Acción — Iteradores Neuronales.
 *
 * Implementa el **Plano de Estado y Acción** ($\mathcal{S} \times \mathcal{A}$)
 * de la arquitectura de Iteradores Neuronales. Este plano es ortogonal al
 * Cósmico y al Rítmico: captura el estado interno del sistema y la intención
 * del iterador en el momento presente.
 *
 * **Estado ($\mathcal{S}$)**: cada dominio operativo (Tálamo, Comandos,
 * Textos, Medios, Direcciones, Mensajes) genera un espin a partir de su
 * `NodoParalelo` más energético en la fase activa. Las entradas $(a, c)$ de la
 * matriz 2×2 se normalizan a un vector en $S^2$. La masa es $\log(1 + E)$,
 * donde $E$ es la energía del nodo.
 *
 * **Acción ($\mathcal{A}$)**: el verbo que el iterador está ejecutando.
 * Cada verbo es un vector fijo en $S^2$ con masa 1.0. Los verbos son:
 * - `APRENDER`   (fase 0)
 * - `PREDECIR`   (fase 1)
 * - `CORREGIR`   (fase 2)
 * - `CONTROLAR`  (fase 3)
 * - `ASCENDER`   (fase 4)
 * - `DESCENDER`  (fase 5)
 *
 * ## Rol en el sistema
 *
 * - El iterador registra el estado de cada dominio antes de cada pulso.
 * - El verbo actual se establece según la operación que se va a ejecutar.
 * - El ramillete de espines de Estado×Acción se compone matricialmente con
 *   los espines Cósmicos y Rítmicos para formar la llave contextual completa.
 * - Este plano es el único que el iterador controla directamente; los otros
 *   dos son externos (astros) o semi-externos (ciclos culturales).
 *
 * @package Iteradores\Tiempo
 * @author Ignacio David Baigorria
 * @since 1.5.1
 * @version 1.5.1
 */

namespace Iteradores\Tiempo;

use Iteradores\Nucleo\Objeto;

class EstadoxAccion extends Objeto
{
    // ═══════════════════════════════════════════════════════
    // VERBOS PREDEFINIDOS (vectores fijos en S²)
    // ═══════════════════════════════════════════════════════

    /**
     * Verbos de acción con sus vectores fijos en la esfera unitaria $S^2$.
     *
     * Los vectores forman un octaedro regular: seis direcciones ortogonales
     * por pares, completamente deterministas y simétricas. Cada verbo tiene
     * masa 1.0.
     *
     * @var array<string, array{vector: array{x: float, y: float, z: float}, masa: float, fase: int}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private const VERBOS = [
        'APRENDER'  => [
            'vector' => ['x' => 1.0,  'y' => 0.0,  'z' => 0.0],
            'masa'   => 1.0,
            'fase'   => 0,
        ],
        'PREDECIR'  => [
            'vector' => ['x' => -1.0, 'y' => 0.0,  'z' => 0.0],
            'masa'   => 1.0,
            'fase'   => 1,
        ],
        'CORREGIR'  => [
            'vector' => ['x' => 0.0,  'y' => 1.0,  'z' => 0.0],
            'masa'   => 1.0,
            'fase'   => 2,
        ],
        'CONTROLAR' => [
            'vector' => ['x' => 0.0,  'y' => -1.0, 'z' => 0.0],
            'masa'   => 1.0,
            'fase'   => 3,
        ],
        'ASCENDER'  => [
            'vector' => ['x' => 0.0,  'y' => 0.0,  'z' => 1.0],
            'masa'   => 1.0,
            'fase'   => 4,
        ],
        'DESCENDER' => [
            'vector' => ['x' => 0.0,  'y' => 0.0,  'z' => -1.0],
            'masa'   => 1.0,
            'fase'   => 5,
        ],
    ];

    // ═══════════════════════════════════════════════════════
    // ESTADO INTERNO
    // ═══════════════════════════════════════════════════════

    /**
     * Estado registrado por dominio.
     *
     * Cada entrada contiene:
     * - `a`: entrada (0,0) de la matriz 2×2 del NodoParalelo más energético.
     * - `c`: entrada (1,0) de la matriz 2×2.
     * - `energia`: energía del nodo ($E_{D,F}$).
     *
     * @var array<string, array{a: float, c: float, energia: float}>
     */
    private array $estados = [];

    /**
     * Verbo actual del iterador.
     * @var string
     */
    private string $verbo_actual = 'APRENDER';

    /**
     * Último ramillete de espines calculado (caché).
     *
     * @var array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>|null
     */
    private ?array $ultimo_espines = null;

    /**
     * Último vector de activación calculado (caché).
     * @var array{x: float, y: float, z: float}|null
     */
    private ?array $ultimo_vector = null;

    /**
     * Construye el plano Estado×Acción con el verbo por defecto.
     *
     * @param string $verbo_inicial Verbo inicial (por defecto 'APRENDER').
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function __construct(string $verbo_inicial = 'APRENDER')
    {
        $this->establecer_verbo($verbo_inicial);
    }

    // ═══════════════════════════════════════════════════════
    // GESTIÓN DE ESTADO
    // ═══════════════════════════════════════════════════════

    /**
     * Registra el estado de un dominio operativo.
     *
     * El estado se deriva del `NodoParalelo` más energético del dominio en
     * la fase activa. Las entradas $(a, c)$ de su matriz 2×2 se normalizan
     * para formar la dirección del espin; la energía $E$ determina la masa
     * como $\log(1 + E)$.
     *
     * @param string $dominio  Nombre del dominio (ej: 'Textos', 'Comandos').
     * @param float  $a        Entrada (0,0) de la matriz 2×2.
     * @param float  $c        Entrada (1,0) de la matriz 2×2.
     * @param float  $energia  Energía del nodo ($E \geq 0$).
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function registrar_estado(string $dominio, float $a, float $c, float $energia): void
    {
        $this->estados[$dominio] = [
            'a'       => $a,
            'c'       => $c,
            'energia' => max(0.0, $energia),
        ];
        $this->invalidar_cache();
    }

    /**
     * Elimina el estado de un dominio.
     *
     * @param string $dominio Nombre del dominio.
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function eliminar_estado(string $dominio): void
    {
        unset($this->estados[$dominio]);
        $this->invalidar_cache();
    }

    /**
     * Devuelve el estado registrado de un dominio.
     *
     * @param string $dominio Nombre del dominio.
     * @return array{a: float, c: float, energia: float}|null
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function estado(string $dominio): ?array
    {
        return $this->estados[$dominio] ?? null;
    }

    /**
     * Devuelve todos los estados registrados.
     *
     * @return array<string, array{a: float, c: float, energia: float}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function estados_registrados(): array
    {
        return $this->estados;
    }

    // ═══════════════════════════════════════════════════════
    // GESTIÓN DE ACCIÓN
    // ═══════════════════════════════════════════════════════

    /**
     * Establece el verbo actual del iterador.
     *
     * El verbo determina la intención del pulso: aprender, predecir,
     * corregir, controlar, ascender o descender.
     *
     * @param string $verbo Uno de: APRENDER, PREDECIR, CORREGIR,
     *                      CONTROLAR, ASCENDER, DESCENDER.
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function establecer_verbo(string $verbo): void
    {
        $verbo = strtoupper($verbo);
        if (!isset(self::VERBOS[$verbo])) {
            self::_error("Verbo '{$verbo}' no reconocido. " .
                "Use: APRENDER, PREDECIR, CORREGIR, CONTROLAR, ASCENDER, DESCENDER.");
            return;
        }
        $this->verbo_actual = $verbo;
        $this->invalidar_cache();
    }

    /**
     * Devuelve el verbo actual.
     *
     * @return string
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function verbo(): string
    {
        return $this->verbo_actual;
    }

    /**
     * Devuelve la fase numérica del verbo actual.
     *
     * @return int Fase entre 0 y 5.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function fase_verbo(): int
    {
        return self::VERBOS[$this->verbo_actual]['fase'];
    }

    /**
     * Devuelve todos los verbos disponibles.
     *
     * @return array<string, array{vector: array{x: float, y: float, z: float}, masa: float, fase: int}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function verbos_disponibles(): array
    {
        return self::VERBOS;
    }

    // ═══════════════════════════════════════════════════════
    // RAMILLETE DE ESPINES
    // ═══════════════════════════════════════════════════════

    /**
     * Devuelve el ramillete de espines del plano Estado×Acción.
     *
     * El ramillete contiene:
     * - Un espin por cada dominio registrado (tipo 'Estado').
     * - Un espin para el verbo actual (tipo 'Accion').
     *
     * Cada espin es un array asociativo con:
     * - `nombre`: identificador (dominio o verbo).
     * - `tipo`: 'Estado' o 'Accion'.
     * - `masa`: masa gravitacional.
     * - `vector`: vector unitario en $S^2$.
     *
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function espines(): array
    {
        if ($this->ultimo_espines !== null) {
            return $this->ultimo_espines;
        }

        $this->ultimo_espines = array_merge(
            self::calcular_espines_estado($this->estados),
            [self::calcular_espin_accion($this->verbo_actual)]
        );

        return $this->ultimo_espines;
    }

    /**
     * Devuelve el espin de estado de un dominio específico.
     *
     * @param string $dominio Nombre del dominio.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}|null
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function espin_estado(string $dominio): ?array
    {
        if (!isset($this->estados[$dominio])) {
            self::_error("Dominio '{$dominio}' no tiene estado registrado.");
            return null;
        }

        return self::construir_espin_estado($dominio, $this->estados[$dominio]);
    }

    /**
     * Devuelve el espin de acción (verbo actual).
     *
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function espin_accion(): array
    {
        return self::calcular_espin_accion($this->verbo_actual);
    }

    /**
     * Calcula el vector de activación del plano Estado×Acción.
     *
     * Es la suma ponderada por masa de todos los espines de estado más el
     * espin de acción, normalizada a unitario.
     *
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function vector_activacion(): array
    {
        if ($this->ultimo_vector !== null) {
            return $this->ultimo_vector;
        }

        $espines = $this->espines();
        $this->ultimo_vector = self::activacion_desde_espines($espines);

        return $this->ultimo_vector;
    }

    /**
     * Alias de `vector_activacion()` para consistencia con los demás relojes.
     *
     * @return array{x: float, y: float, z: float} Vector unitario.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function vector(): array
    {
        return $this->vector_activacion();
    }

    // ═══════════════════════════════════════════════════════
    // CÁLCULOS INTERNOS
    // ═══════════════════════════════════════════════════════

    /**
     * Calcula los espines de estado para todos los dominios registrados.
     *
     * @param array<string, array{a: float, c: float, energia: float}> $estados
     * @return array<array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}>
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_espines_estado(array $estados): array
    {
        $espines = [];
        foreach ($estados as $dominio => $datos) {
            $espines[] = self::construir_espin_estado($dominio, $datos);
        }
        return $espines;
    }

    /**
     * Construye un espin de estado a partir de los datos de un dominio.
     *
     * Las entradas $(a, c)$ de la matriz 2×2 se normalizan:
     * $$\hat{s}_{D,F} = \left(\frac{a}{\sqrt{a^2+c^2}}, \; \frac{c}{\sqrt{a^2+c^2}}, \; 0\right)$$
     *
     * La masa es $m_{D,F} = \log(1 + E_{D,F})$.
     *
     * @param string $dominio Nombre del dominio.
     * @param array{a: float, c: float, energia: float} $datos
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function construir_espin_estado(string $dominio, array $datos): array
    {
        $a = $datos['a'];
        $c = $datos['c'];
        $energia = $datos['energia'];

        $norm = sqrt($a * $a + $c * $c);
        if ($norm < 1e-9) {
            $vector = ['x' => 1.0, 'y' => 0.0, 'z' => 0.0];
        } else {
            $vector = [
                'x' => $a / $norm,
                'y' => $c / $norm,
                'z' => 0.0,
            ];
        }

        $masa = log(1.0 + $energia);

        return [
            'nombre' => $dominio,
            'tipo'   => 'Estado',
            'masa'   => $masa,
            'vector' => $vector,
        ];
    }

    /**
     * Calcula el espin de acción para un verbo dado.
     *
     * @param string $verbo Nombre del verbo.
     * @return array{nombre: string, tipo: string, masa: float, vector: array{x: float, y: float, z: float}}
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private static function calcular_espin_accion(string $verbo): array
    {
        $config = self::VERBOS[$verbo];
        return [
            'nombre' => $verbo,
            'tipo'   => 'Accion',
            'masa'   => $config['masa'],
            'vector' => $config['vector'],
        ];
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
            return ['x' => 1.0, 'y' => 0.0, 'z' => 0.0];
        }

        return [
            'x' => $x / $magnitud,
            'y' => $y / $magnitud,
            'z' => $z / $magnitud,
        ];
    }

    /**
     * Invalida la caché interna.
     *
     * Se invoca automáticamente al registrar/eliminar estados o cambiar el verbo.
     *
     * @return void
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function invalidar_cache(): void
    {
        $this->ultimo_espines = null;
        $this->ultimo_vector = null;
    }
}
