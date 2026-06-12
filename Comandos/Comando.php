<?php
namespace Iteradores\Comandos;

/**
 * Define el contrato mínimo que debe cumplir un comando ejecutable.
 *
 * Cada comando del sistema implementa esta interfaz, lo que permite
 * que el {@link Controlador} lo registre y ejecute sin conocer su
 * lógica interna.
 *
 * Un comando puede declarar si solo está disponible en desarrollo
 * y, opcionalmente, proporcionar una función de reversa para
 * deshacer sus efectos.
 *
 * A partir de la versión 1.3.2, el comando también declara sus
 * metadatos (descripción, parámetros esperados y ejemplos de uso)
 * para que el Controlador pueda validar argumentos y generar
 * automáticamente la ayuda.
 *
 * @package Iteradores\Comandos
 * @since 1.3.1
 * @version 1.3.2
 */
interface Comando
{
    /**
     * Nombre único del comando (ej. 'debug:imprimir').
     *
     * @return string
     */
    public static function nombre(): string;

    /**
     * Indica si el comando solo debe registrarse en entorno de desarrollo.
     *
     * @return bool
     */
    public static function solo_desarrollo(): bool;

    /**
     * Breve descripción de lo que hace el comando.
     *
     * Se utiliza en la ayuda generada automáticamente por el Controlador.
     *
     * @return string
     * @since 1.3.2
     */
    public static function descripcion(): string;

    /**
     * Define los parámetros que acepta el comando.
     *
     * Cada entrada del array es un array asociativo con las siguientes claves:
     * - 'nombre'      (string)  Nombre del parámetro (sin guiones).
     * - 'tipo'        (string)  'posicional', 'bandera' u 'opcion'.
     * - 'obligatorio' (bool)    Si es obligatorio (solo para posicionales y opciones).
     * - 'defecto'     (mixed)   Valor por defecto si no se proporciona.
     * - 'descripcion' (string)  Texto explicativo para la ayuda.
     * - 'valores'     (array)   (Opcional) Lista de valores permitidos.
     *
     * Los nombres de los parámetros no pueden coincidir con las palabras
     * reservadas definidas en {@link \Iteradores\Configuracion\Conf::PALABRAS_RESERVADAS_COMANDOS}.
     *
     * @return array
     * @since 1.3.2
     */
    public static function parametros(): array;

    /**
     * Proporciona uno o varios ejemplos de uso del comando.
     *
     * Cada entrada es un string con un ejemplo completo (incluyendo el nombre
     * del comando y los argumentos).
     *
     * @return string[]
     * @since 1.3.2
     */
    public static function ejemplos(): array;

    /**
     * Ejecuta la lógica del comando.
     *
     * Recibe los argumentos ya validados y normalizados por el Controlador.
     * La estructura del parámetro `$args` depende de si el comando ha
     * declarado una definición de parámetros mediante el método
     * {@link parametros()}:
     *
     * - **Con definición de parámetros:** `$args` es un array asociativo con
     *   las claves `'posicionales'` (array numérico), `'banderas'`
     *   (array asociativo `nombre => bool`) y `'opciones'`
     *   (array asociativo `nombre => valor`).
     * - **Sin definición de parámetros:** `$args` es un array numérico que
     *   contiene los argumentos crudos tal como fueron pasados al comando.
     *
     * @param string $token Token de seguridad proporcionado por el Controlador.
     * @param array  $args  Argumentos normalizados (array asociativo con
     *                      'posicionales', 'banderas' y 'opciones', o bien
     *                      un array numérico de argumentos crudos).
     * @return mixed Resultado de la ejecución.
     */
   public function ejecutar(string $token, array $args): mixed;

    /**
     * Proporciona una función de reversa, o `null` si el comando no es reversible.
     *
     * La función devuelta debe aceptar el mismo token y argumentos
     * que {@link ejecutar()}.
     *
     * @return callable|null
     */
    public function reversa(): ?callable;
}