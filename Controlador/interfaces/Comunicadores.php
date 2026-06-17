<?php
namespace Iteradores\Controlador\Interfaces;
use Iteradores\Comunicadores\Comunicador; 

/**
 * Define el contrato para la gestión centralizada de comunicadores.
 *
 * Mantiene un mapa de comunicadores registrados (uno por tipo),
 * verifica permisos y proporciona acceso controlado a ellos.
 *
 * También expone un método de conveniencia para escribir directamente
 * en la salida estándar configurada según el entorno para usar en depuracion.
 * 
 * @package Iteradores\Comunicadores
 * @since 1.3.3
 */
interface Comunicadores
{
    /**
     * Registra un comunicador a partir de una clase que implementa {@link Comunicador}.
     * 
     * @param string $clase Nombre cualificado de la clase.
     * @return bool
     */
    public static function registrar_comunicador_desde_clase(string $clase): bool;

    /**
     * Registra un comunicador a partir de una instancia.
     *
     * @param Comunicador $comunicador Instancia del comunicador.
     * @return bool
     */
    public static function registrar_comunicador_desde_instancia(Comunicador $comunicador): bool;

    /**
     * Encola un comunicador para registro diferido o inmediato.
     *
     * Acepta tanto un string (nombre de clase) como una instancia de {@link Comunicador}.
     *
     * @param string|Comunicador $comunicador Clase o instancia.
     * @return void
     */
    public static function encolar_comunicador(string|Comunicador $comunicador): void;

    /**
     * Procesa la lista de comunicadores pendientes y los registra.
     *
     * @return int Número de comunicadores registrados exitosamente.
     */
    public static function cargar_comunicadores_pendientes(): int;

    /**
     * Obtiene la instancia única de un comunicador por su nombre.
     *
     * Si se invoca sin argumentos (o con el valor especial `'predeterminado'`),
     * devuelve automáticamente el comunicador de salida estándar correspondiente
     * al entorno actual:
     * - En **consola** → `salida_depuracion_consola`
     * - En **navegador** → `salida_depuracion_html`
     *
     * En cualquier otro caso, busca el comunicador en el mapa interno y verifica
     * que el usuario actual tenga permiso para utilizarlo mediante
     * {@link tiene_permiso()}.
     *
     * Si el comunicador no existe o el usuario no tiene permiso, retorna `null`
     * y registra un error.
     *
     * @param string $nombre Nombre del comunicador (ej. `'archivo'`, `'http'`).
     *                       Si se omite o es `'predeterminado'`, se usa la salida
     *                       estándar según el entorno.
     *
     * @return Comunicador|null La instancia del comunicador,
     *                          o `null` si no está disponible.
     *
     * @example
     * // Obtener la salida estándar (consola o HTML según Entorno)
     * $salida = Controlador::comunicador();
     * $salida->enviar('', 'Hola mundo');
     *
     * // Obtener un comunicador específico
     * $http = Controlador::comunicador('http');
     * if ($http) {
     *     $http->enviar('https://api.example.com', $datos);
     * }
     *
     * @since 1.3.3
     */
    public static function comunicador(string $nombre = 'predeterminado'): ?Comunicador;

    /**
     * Verifica si el usuario actual tiene permiso para usar el comunicador.
     *
     * Placeholder que siempre retorna `true`.
     *
     * @param string $nombre Nombre del comunicador.
     * @return bool
     */
    public static function tiene_permiso(string $nombre): bool;

        /**
     * Escribe un mensaje en la salida estándar configurada según el entorno.
     *
     * Obtiene el comunicador predeterminado (resuelto por
     * {@link comunicador()}) y envía el mensaje a través de él.
     *
     * Es el equivalente a `echo` o `console.log`, pero adaptado al
     * tipo de salida definido en {@link \Iteradores\Configuracion\Entorno}.
     *
     * @param string $mensaje Texto a escribir en la salida estándar.
     *
     * @return void
     *
     * @example
     * Controlador::escribir_salida("Operación completada.");
     *
     * @since 1.3.3
     */
    public static function escribir_salida(string $mensaje): void;
}