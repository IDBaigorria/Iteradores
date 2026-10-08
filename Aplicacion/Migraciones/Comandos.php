<?php
/**
 * Comandos de migración de la aplicación.
 *
 * Se registran directo (no se encolan) porque el Controlador
 * ya está inicializado cuando se carga este archivo desde
 * index.php (los require_once de la app van después del
 * require_once del Controlador, que se autoinicializa).
 *
 * El modelo de marcador: un enlace desde
 * aplicacion/migraciones/<id> hacia aplicacion/migraciones
 * (autoreferencia) indica que la migración fue aplicada.
 *
 * @since 1.5piloto.76n
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;

require_once __DIR__ . '/Registro.php';

/**
 * Registra los comandos app:migracion_* en el Controlador.
 *
 * @return void
 */
function registrar_comandos_migraciones(): void {
    // ─── app:migracion_listar ───────────────────────────
    Controlador::registrar_comando('app:migracion_listar', function(string $token, array $args) {
        $contenedor = _migraciones_contenedor();
        if (!$contenedor) {
            return ['exito' => false, 'error' => 'No existe el contenedor aplicacion/migraciones.'];
        }

        $migraciones = migraciones_registradas();
        $resultado = [];
        $auto_marcadas = 0;

        foreach ($migraciones as $id => $info) {
            $marcada = $contenedor->adyacente($id) !== null;

            // Auto-detectar si está aplicada pero sin marcador.
            if (!$marcada && is_callable($info['detectar'])) {
                try {
                    $detectada = (bool) call_user_func($info['detectar'], $token);
                } catch (\Throwable $e) {
                    $detectada = false;
                }
                if ($detectada) {
                    $contenedor->_adyacente_en($contenedor, $id);
                    $marcada = true;
                    $auto_marcadas++;
                }
            }

            $resultado[] = [
                'id' => $id,
                'nombre' => $info['nombre'],
                'descripcion' => $info['descripcion'],
                'aplicada' => $marcada,
            ];
        }

        return [
            'exito' => true,
            'migraciones' => $resultado,
            'auto_marcadas' => $auto_marcadas,
        ];
    }, null, false);

    // ─── app:migracion_aplicar ──────────────────────────
    Controlador::registrar_comando('app:migracion_aplicar', function(string $token, array $args) {
        $id = (string)($args[0]['id'] ?? '');
        if ($id === '') {
            return ['exito' => false, 'error' => 'Falta el id de la migración.'];
        }

        $migraciones = migraciones_registradas();
        if (!isset($migraciones[$id])) {
            return ['exito' => false, 'error' => "Migración desconocida: $id."];
        }

        $contenedor = _migraciones_contenedor();
        if (!$contenedor) {
            return ['exito' => false, 'error' => 'No existe el contenedor aplicacion/migraciones.'];
        }

        if ($contenedor->adyacente($id)) {
            return ['exito' => false, 'error' => "La migración '$id' ya estaba aplicada."];
        }

        $info = $migraciones[$id];
        $res = ['exito' => false, 'detalles' => []];
        try {
            $res = (array) call_user_func($info['aplicar'], $token);
        } catch (\Throwable $e) {
            return ['exito' => false, 'error' => 'Excepción: ' . $e->getMessage()];
        }

        if (empty($res['exito'])) {
            return ['exito' => false, 'error' => 'La migración no se completó.', 'detalles' => $res['detalles'] ?? []];
        }

        // Marcar.
        $contenedor->_adyacente_en($contenedor, $id);
        return ['exito' => true, 'detalles' => $res['detalles'] ?? []];
    }, null, false);

    // ─── app:migracion_marcar ───────────────────────────
    Controlador::registrar_comando('app:migracion_marcar', function(string $token, array $args) {
        $id = (string)($args[0]['id'] ?? '');
        $aplicada = (bool)($args[0]['aplicada'] ?? false);
        if ($id === '') {
            return ['exito' => false, 'error' => 'Falta el id.'];
        }
        $contenedor = _migraciones_contenedor();
        if (!$contenedor) {
            return ['exito' => false, 'error' => 'No existe el contenedor.'];
        }
        if ($aplicada) {
            if (!$contenedor->adyacente($id)) {
                $contenedor->_adyacente_en($contenedor, $id);
            }
        } else {
            if ($contenedor->adyacente($id)) {
                $contenedor->eliminar_adyacente($id);
            }
        }
        return ['exito' => true];
    }, null, false);
}

/**
 * Devuelve el nodo contenedor aplicacion/migraciones, o null.
 *
 * @return \Iteradores\Nodos\Nodo|null
 */
function _migraciones_contenedor() {
    $aplicacion = Nodo::nodo_por_id('aplicacion');
    if (!$aplicacion) return null;
    return $aplicacion->adyacente('migraciones');
}
?>