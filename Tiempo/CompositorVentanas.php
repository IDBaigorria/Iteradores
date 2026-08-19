<?php

/**
 * CompositorVentanas - De espines a llave matricial única
 * 
 * Mapea cada espin atómico de los tres planos a un primo único (>100) y
 * compone una matriz 2×2 no conmutativa producto de todas las matrices
 * canónicas. Estados de dominio dinámicos reciben primos asignados
 * automáticamente a partir del 191.
 * 
 * @author Ignacio David Baigorria
 * @since 1.0.0
 * @version 1.5.1
 */
class CompositorVentanas
{
    /**
     * Primos del plano cósmico.
     * @var array<string, int>
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PRIMOS_COSMICO = [
        'sol'           => 101,
        'luna'          => 103,
        'jupiter'       => 107,
        'eje_terrestre' => 109,
        'centro_tierra' => 113,
    ];

    /**
     * Primos del plano rítmico.
     * @var array<string, int>
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PRIMOS_RITMICO = [
        'dia_noche'      => 127,
        'semana'         => 131,
        'anno'           => 137,
        'hora'           => 139,
        'minuto'         => 149,
        'prisma_ritmico' => 151,
    ];

    /**
     * Primos del plano de acción.
     * @var array<string, int>
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PRIMOS_ACCION = [
        'APRENDER'  => 157,
        'PREDECIR'  => 163,
        'CORREGIR'  => 167,
        'CONTROLAR' => 173,
        'ASCENDER'  => 179,
        'DESCENDER' => 181,
    ];

    /**
     * Primo base para estados dinámicos.
     * @var int
     * @since 1.5.1
     * @version 1.5.1
     */
    private const PRIMO_BASE_ESTADO = 191;

    /**
     * Mapa de dominios a primos dinámicos.
     * @var array<string, int>
     * @since 1.5.1
     * @version 1.5.1
     */
    private array $primos_estado = [];

    /**
     * Contador para asignación de primos dinámicos.
     * @var int
     * @since 1.5.1
     * @version 1.5.1
     */
    private int $contador_estado = 0;

    /**
     * Genera la matriz 2×2 canónica para un primo.
     * 
     * @param int $primo Primo a mapear.
     * @return array Matriz {a, b, c, d}.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function matriz_canonica(int $primo): array
    {
        return [
            'a' => $primo,
            'b' => 0,
            'c' => 0,
            'd' => 1,
        ];
    }

    /**
     * Producto no conmutativo de dos matrices 2×2.
     * 
     * @param array $m1 Primera matriz {a, b, c, d}.
     * @param array $m2 Segunda matriz {a, b, c, d}.
     * @return array Producto {a, b, c, d}.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function producto(array $m1, array $m2): array
    {
        return [
            'a' => $m1['a'] * $m2['a'] + $m1['b'] * $m2['c'],
            'b' => $m1['a'] * $m2['b'] + $m1['b'] * $m2['d'],
            'c' => $m1['c'] * $m2['a'] + $m1['d'] * $m2['c'],
            'd' => $m1['c'] * $m2['b'] + $m1['d'] * $m2['d'],
        ];
    }

    /**
     * Resuelve el primo para un espin, asignando dinámicamente si es estado.
     * 
     * @param array $espin Espin con nombre y tipo.
     * @return int|null Primo asignado o null.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function resolver_primo(array $espin): ?int
    {
        $nombre = $espin['nombre'];
        $tipo = $espin['tipo'];

        if (isset(self::PRIMOS_COSMICO[$nombre])) {
            return self::PRIMOS_COSMICO[$nombre];
        }

        if (isset(self::PRIMOS_RITMICO[$nombre])) {
            return self::PRIMOS_RITMICO[$nombre];
        }

        if (isset(self::PRIMOS_ACCION[$nombre])) {
            return self::PRIMOS_ACCION[$nombre];
        }

        if ($tipo === 'Estado') {
            if (!isset($this->primos_estado[$nombre])) {
                $this->primos_estado[$nombre] = $this->siguiente_primo_estado();
            }
            return $this->primos_estado[$nombre];
        }

        return null;
    }

    /**
     * Genera el siguiente primo disponible para estados dinámicos.
     * 
     * @return int Primo asignado.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function siguiente_primo_estado(): int
    {
        $candidato = self::PRIMO_BASE_ESTADO + $this->contador_estado;
        $this->contador_estado++;

        while (!$this->es_primo($candidato)) {
            $candidato++;
            $this->contador_estado++;
        }

        return $candidato;
    }

    /**
     * Verifica si un número es primo.
     * 
     * @param int $n Número a verificar.
     * @return bool True si es primo.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    private function es_primo(int $n): bool
    {
        if ($n < 2) return false;
        if ($n === 2) return true;
        if ($n % 2 === 0) return false;
        $limite = (int)sqrt($n);
        for ($i = 3; $i <= $limite; $i += 2) {
            if ($n % $i === 0) return false;
        }
        return true;
    }

    /**
     * Compone la llave matricial única a partir de tres ramilletes de espines.
     * 
     * @param array $espines_cosmicos Espines del plano cósmico.
     * @param array $espines_ritmicos Espines del plano rítmico.
     * @param array $espines_estado_accion Espines del plano estado×acción.
     * @return array Matriz 2×2 resultante {a, b, c, d}.
     * @author Ignacio David Baigorria
     * @since 1.0.0
     * @version 1.5.1
     */
    public function componer(array $espines_cosmicos, array $espines_ritmicos, array $espines_estado_accion): array
    {
        $todos = array_merge($espines_cosmicos, $espines_ritmicos, $espines_estado_accion);

        $resultado = [
            'a' => 1,
            'b' => 0,
            'c' => 0,
            'd' => 1,
        ];

        foreach ($todos as $espin) {
            $primo = $this->resolver_primo($espin);
            if ($primo === null) {
                continue;
            }
            $m = $this->matriz_canonica($primo);
            $resultado = $this->producto($resultado, $m);
        }

        return $resultado;
    }

    /**
     * Devuelve el p-grama (secuencia de primos) de una composición.
     * 
     * @param array $espines_cosmicos Espines del plano cósmico.
     * @param array $espines_ritmicos Espines del plano rítmico.
     * @param array $espines_estado_accion Espines del plano estado×acción.
     * @return array Lista de primos en orden de composición.
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function p_grama(array $espines_cosmicos, array $espines_ritmicos, array $espines_estado_accion): array
    {
        $todos = array_merge($espines_cosmicos, $espines_ritmicos, $espines_estado_accion);
        $primos = [];

        foreach ($todos as $espin) {
            $primo = $this->resolver_primo($espin);
            if ($primo !== null) {
                $primos[] = $primo;
            }
        }

        return $primos;
    }

    /**
     * Resetea los primos de estado dinámicos.
     * 
     * @author Ignacio David Baigorria
     * @since 1.5.1
     * @version 1.5.1
     */
    public function resetear_estados(): void
    {
        $this->primos_estado = [];
        $this->contador_estado = 0;
    }
}
