<?php
namespace Iteradores\Comandos\Prueba;

use Iteradores\Comandos\Comando;
use Iteradores\Controlador\Controlador;
use Iteradores\Nodos\NodoElectrico;

/**
 * Comando de prueba que crea un nodo eléctrico y permite deshacer la creación.
 *
 * Acepta un argumento posicional `dato` obligatorio y las opciones
 * `--capacidad` y `--fuga` opcionales.
 *
 * **Reversible:** Sí – elimina el nodo creado.
 *
 * @package Iteradores\Comandos\Prueba
 * @since 1.3.2
 */
class CrearNodo implements Comando
{
    private int $nodo_creado_id = 0;   // ← inicialización por defecto

    public static function nombre(): string { return 'prueba:crear_nodo'; }
    public static function solo_desarrollo(): bool { return false; }

    public static function descripcion(): string
    {
        return 'Crea un nodo eléctrico con el dato indicado. (Comando de prueba reversible)';
    }

    public static function parametros(): array
    {
        return [
            [
                'nombre'      => 'dato',
                'tipo'        => 'posicional',
                'obligatorio' => true,
                'descripcion' => 'Dato a encapsular en el nuevo nodo.',
            ],
            [
                'nombre'      => 'capacidad',
                'tipo'        => 'opcion',
                'obligatorio' => false,
                'defecto'     => 100,
                'descripcion' => 'Capacidad máxima del nodo (por defecto 100).',
            ],
            [
                'nombre'      => 'fuga',
                'tipo'        => 'opcion',
                'obligatorio' => false,
                'defecto'     => 0,
                'descripcion' => 'Fuga de energía por ciclo (por defecto 0).',
            ],
        ];
    }

    public static function ejemplos(): array
    {
        return [
            "prueba:crear_nodo 'Sensor'",
            "prueba:crear_nodo 'Motor' --capacidad=200 --fuga=0.5",
        ];
    }

    public function ejecutar(string $token, array $args): mixed
    {
        $dato = $args['posicionales'][0] ?? 'Nodo de prueba';
        $capacidad = (int)($args['opciones']['capacidad'] ?? 100);
        $fuga = (float)($args['opciones']['fuga'] ?? 0);

        $nodo = NodoElectrico::crear_con_dato($dato, false, $capacidad, $fuga);
        $this->nodo_creado_id = $nodo->id();

        return "Nodo creado con id: {$this->nodo_creado_id}";
    }

    public function reversa(): ?callable
    {
        $id = $this->nodo_creado_id;   // ya está inicializado (al menos con 0)
        return function(string $token, array $args) use ($id) {
            $nodo = NodoElectrico::existe($id) ? NodoElectrico::nodo_por_id($id) : null;
            if ($nodo === null) {
                return "El nodo $id ya no existe.";
            }
            NodoElectrico::eliminar($nodo);
            return "Nodo $id eliminado por reversa.";
        };
    }
}

Controlador::encolar_comando(CrearNodo::class);