<?php
/**
 * Helpers para mantener vivos los compartidos por terminal.
 *
 * Fase B2.4 del modelo topológico. Los compartidos de un dueño
 * (`compartido_con_us_termX`) tienen que reflejar en cada momento
 * qué viajes están autorizados a esa terminal, qué empresas usan
 * esos viajes, y qué ventas y cancelaciones son de esa terminal.
 *
 * Los 3 helpers que ya existían (`_asegurar_compartido_terminal`,
 * `_asegurar_arbol_compartido`, `_asegurar_compartido_y_repuntar`)
 * siguen viviendo en `Usuario.php`. Los 5 nuevos (sincronizar /
 * desincronizar viaje, terminal, empresa) viven acá porque los
 * usan también `Viaje.php`, `ViajeMicros.php` y `Empresa.php`.
 *
 * @package   Iteradores
 * @since     1.5piloto.77q
 * @version   1.5piloto.77q
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");
include_once("./miscelaneas/Arbol.php");
include_once("./Aplicacion/FuncionesAuxiliares.php");
// Los 3 helpers `_asegurar_*` viven en Usuario.php. Este
// require los garantiza para quien use Compartidos.php
// sin haber cargado Usuario.php antes.
require_once __DIR__ . '/Usuarios/Usuario.php';

// ============================================================
// Fase B2.4: sincronización de viajes y empresas
// ============================================================

/**
 * Sincroniza un viaje en el compartido de una terminal:
 * agrega el viaje al contenedor `viajes` (si no está) y
 * agrega las empresas referenciadas por sus micros al
 * contenedor `empresas` (si no están). Idempotente.
 *
 * No modifica nada si el viaje ya estaba y todas las
 * empresas también.
 *
 * @param Nodo   $compartido
 * @param Nodo   $nodo_viaje
 * @param string $nombre_terminal
 * @param Nodo   $nodo_dueno
 * @return void
 */
function _sincronizar_viaje_en_compartido(Nodo $compartido, Nodo $nodo_viaje, string $nombre_terminal, Nodo $nodo_dueno): void {
    // 1. Asegurar el contenedor `viajes` del compartido.
    $c_viajes = $compartido->adyacente('viajes');
    if (!$c_viajes) {
        $c_viajes = Nodo::crear_con_dato('');
        $compartido->_adyacente_en($c_viajes, 'viajes');
    }
    $nombre_viaje = (string)$nodo_viaje->dato();
    if ($nombre_viaje !== '' && !$c_viajes->adyacente($nombre_viaje)) {
        $c_viajes->_adyacente_en($nodo_viaje, $nombre_viaje);
    }

    // 2. Asegurar el contenedor `empresas` del compartido.
    $c_empresas = $compartido->adyacente('empresas');
    if (!$c_empresas) {
        $c_empresas = Nodo::crear_con_dato('');
        $compartido->_adyacente_en($c_empresas, 'empresas');
    }

    // 3. Recolectar las empresas referenciadas por los micros del viaje.
    $micros = $nodo_viaje->adyacente('micros');
    if ($micros) {
        foreach ((array)$micros->adyacentes() as $nodo_micro) {
            $emp = $nodo_micro->adyacente('empresa');
            if ($emp) {
                $nombre_emp = (string)$emp->dato();
                if ($nombre_emp !== '' && !$c_empresas->adyacente($nombre_emp)) {
                    $c_empresas->_adyacente_en($emp, $nombre_emp);
                }
            }
        }
    }
}

/**
 * Desincroniza un viaje de un compartido: lo saca del
 * contenedor `viajes`. NO toca `empresas` (podrían estar
 * en uso por otro viaje autorizado de la misma terminal).
 *
 * @param Nodo   $compartido
 * @param Nodo   $nodo_viaje
 * @param string $nombre_terminal
 * @return bool True si el viaje estaba y se sacó.
 */
function _desincronizar_viaje_de_compartido(Nodo $compartido, Nodo $nodo_viaje, string $nombre_terminal): bool {
    $c_viajes = $compartido->adyacente('viajes');
    if (!$c_viajes) return false;
    $nombre_viaje = (string)$nodo_viaje->dato();
    if ($nombre_viaje !== '' && $c_viajes->adyacente($nombre_viaje)) {
        $c_viajes->eliminar_adyacente($nombre_viaje);
        return true;
    }
    return false;
}

/**
 * Sincroniza UNA terminal autorizada en UN viaje:
 * asegura el compartido de la terminal en el dueño y
 * agrega el viaje + sus empresas. Idempotente.
 *
 * Se llama desde `agregar_terminal_autorizada` y desde
 * `agregar_micro_a_viaje` (para propagar la empresa nueva).
 *
 * @param Nodo   $nodo_viaje
 * @param string $nombre_terminal
 * @param string $nombre_dueno
 * @return void
 */
function _sincronizar_terminal_en_compartido_del_viaje(Nodo $nodo_viaje, string $nombre_terminal, string $nombre_dueno): void {
    $nodo_dueno = Nodo::nodo_por_id('us_' . $nombre_dueno);
    if (!$nodo_dueno) return;
    $nodo_terminal = Nodo::nodo_por_id('us_' . $nombre_terminal);
    if (!$nodo_terminal) return;

    // Asegurar el compartido (crea si falta, repunta).
    _asegurar_compartido_y_repuntar($nodo_dueno, $nodo_terminal, $nombre_dueno, $nombre_terminal);

    // Obtener el compartido y sincronizar el viaje.
    $compartido = $nodo_dueno->adyacente('compartido_con_' . $nombre_terminal);
    if (!$compartido) return;
    _sincronizar_viaje_en_compartido($compartido, $nodo_viaje, $nombre_terminal, $nodo_dueno);
}

/**
 * Desincroniza UNA terminal autorizada de UN viaje:
 * saca el viaje del compartido del terminal. Idempotente.
 *
 * Se llama desde `eliminar_terminal_autorizada`.
 *
 * @param Nodo   $nodo_viaje
 * @param string $nombre_terminal
 * @return void
 */
function _desincronizar_terminal_de_compartido_del_viaje(Nodo $nodo_viaje, string $nombre_terminal): void {
    $nodo_terminal = Nodo::nodo_por_id('us_' . $nombre_terminal);
    if (!$nodo_terminal) return;
    $nodo_dueno_ctx = $nodo_terminal->adyacente('dueno');
    if (!$nodo_dueno_ctx) return;
    // Solo si el enlace `dueno` apunta a un compartido (post-repuntado).
    if ($nodo_dueno_ctx->adyacente('_es_compartido')) {
        _desincronizar_viaje_de_compartido($nodo_dueno_ctx, $nodo_viaje, $nombre_terminal);
    }
}

/**
 * Desincroniza un viaje de TODOS los compartidos de sus
 * terminales autorizadas (o de una sola, si se especifica).
 * Idempotente.
 *
 * Se llama desde `eliminar_viaje` (antes de destruir el
 * subárbol, para que no queden referencias entrantes).
 *
 * @param Nodo         $nodo_viaje
 * @param string|null  $nombre_terminal_especifico
 * @return void
 */
function _desincronizar_viaje_de_compartidos(Nodo $nodo_viaje, ?string $nombre_terminal_especifico = null): void {
    $terminales = $nodo_viaje->adyacente('terminales_autorizadas');
    if (!$terminales) return;
    foreach ((array)$terminales->adyacentes() as $nombre_terminal => $nodo_tv) {
        $nombre_terminal = (string)$nombre_terminal;
        if ($nombre_terminal === '') continue;
        if ($nombre_terminal_especifico !== null && $nombre_terminal !== $nombre_terminal_especifico) continue;
        _desincronizar_terminal_de_compartido_del_viaje($nodo_viaje, $nombre_terminal);
    }
}

/**
 * Desincroniza una empresa de TODOS los compartidos del
 * dueño. Saca el enlace del contenedor `empresas` de cada
 * `compartido_con_*`. Idempotente.
 *
 * Se llama desde `eliminar_empresa` antes de destruir el
 * nodo empresa (que todavía tiene referencias entrantes
 * desde los compartidos).
 *
 * @param Nodo   $nodo_dueno
 * @param string $nombre_empresa
 * @return void
 */
function _desincronizar_empresa_de_compartidos(Nodo $nodo_dueno, string $nombre_empresa): void {
    foreach ((array)$nodo_dueno->adyacentes() as $enlace => $hijo) {
        $enlace = (string)$enlace;
        if (strpos($enlace, 'compartido_con_') !== 0) continue;
        $c_empresas = $hijo->adyacente('empresas');
        if ($c_empresas && $c_empresas->adyacente($nombre_empresa)) {
            $c_empresas->eliminar_adyacente($nombre_empresa);
        }
    }
}