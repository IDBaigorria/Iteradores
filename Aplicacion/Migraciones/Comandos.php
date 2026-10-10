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

    // ─── app:migracion_limpiar_marcadores ───────────────
    //
    // Fix v76y: permite limpiar el testigo persistente de una
    // migración para que la auto-detección vuelva a correr en
    // el próximo listado. Se usa cuando el testigo quedó mal
    // puesto por la auto-detección por vacío de versiones
    // anteriores.
    //
    // Args: ['id' => id_de_migracion] (o vacío para todas).
    Controlador::registrar_comando('app:migracion_limpiar_marcadores', function(string $token, array $args) {
        $id = (string)($args[0]['id'] ?? '');
        $contenedor = _migraciones_contenedor();
        if (!$contenedor) {
            return ['exito' => false, 'error' => 'No existe el contenedor.'];
        }
        if ($id !== '') {
            if ($contenedor->adyacente($id)) {
                $contenedor->eliminar_adyacente($id);
            }
            return ['exito' => true, 'limpiados' => 1];
        }
        // Sin id: limpiar todos los testigos que no correspondan
        // a migraciones del registro actual.
        $registro = migraciones_registradas();
        $limpiados = 0;
        $ady = (array)$contenedor->adyacentes();
        foreach ($ady as $enlace => $_nodo) {
            $enlace = (string)$enlace;
            if ($enlace === '_es_compartido') continue;
            if (!isset($registro[$enlace])) {
                $contenedor->eliminar_adyacente($enlace);
                $limpiados++;
            }
        }
        return ['exito' => true, 'limpiados' => $limpiados];
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

    // ─── app:crear_compartidos_terminal ─────────────────
    //
    // Fase B2.1 del modelo topológico (v76q).
    //
    // Crea el contenedor `compartido_con_us_termX` en cada
    // dueño, uno por cada terminal autorizado en algún viaje
    // del dueño. Estructura del compartido:
    //
    //   us_duenoY/compartido_con_us_termX   (dato=nombre_dueno)
    //   ├── viajes        → solo los autorizados a termX
    //   ├── empresas      → las referenciadas por esos viajes
    //   ├── pasajeros     → alias al contenedor privado del dueño
    //   ├── ventas        → las ventas de termX
    //   ├── cancelaciones → las cancelaciones de termX
    //   └── terminales    → solo us_termX
    //
    // No toca los enlaces viejos: los compartidos coexisten
    // con la estructura actual. El código sigue usando la raíz
    // `usuarios` como hoy. El repuntado es Fase B2.2/B2.3.
    //
    // Args: ['dueno' => nombre | 'todos',
    //        'terminal' => nombre | 'todos']
    // Devuelve: { creados: int, salteados: int, errores: [] }.
    Controlador::registrar_comando('app:crear_compartidos_terminal', function(string $token, array $args) {
        $opciones = $args[0] ?? [];
        $dueno_filtro = (string)($opciones['dueno'] ?? 'todos');
        $terminal_filtro = (string)($opciones['terminal'] ?? 'todos');

        $creados = 0;
        $salteados = 0;
        $errores = [];

        Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$creados, &$salteados, &$errores, $dueno_filtro, $terminal_filtro) {
            $id = (string)$nodo->id();
            if (strpos($id, 'us_') !== 0) return null;

            // Nivel: puede estar en la raíz o dentro de `publico`.
            $nivel_nodo = $nodo->adyacente('nivel');
            if (!$nivel_nodo) {
                $publico = $nodo->adyacente('publico');
                if ($publico) $nivel_nodo = $publico->adyacente('nivel');
            }
            $nivel = $nivel_nodo ? $nivel_nodo->dato() : '';
            if ($nivel !== 'dueno') return null;

            $nombre_dueno = (string)$nodo->dato();
            if ($dueno_filtro !== 'todos' && $nombre_dueno !== $dueno_filtro) return null;

            // Obtener contenedor de viajes (raíz o privado).
            $cont_viajes = $nodo->adyacente('viajes');
            if (!$cont_viajes) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_viajes = $priv->adyacente('viajes');
            }
            if (!$cont_viajes) return null;

            // Recolectar terminales autorizados en algún viaje.
            $ady_viajes = (array)$cont_viajes->adyacentes();
            $terminales_autorizados = [];
            foreach ($ady_viajes as $nv => $nodo_viaje) {
                $tas = $nodo_viaje->adyacente('terminales_autorizadas');
                if (!$tas) continue;
                foreach ((array)$tas->adyacentes() as $nombre_t => $nodo_tv) {
                    $terminales_autorizados[(string)$nombre_t] = true;
                }
            }
            if (empty($terminales_autorizados)) return null;

            // Otros contenedores del dueño.
            $cont_ventas = $nodo->adyacente('ventas');
            if (!$cont_ventas) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_ventas = $priv->adyacente('ventas');
            }
            $cont_pasajeros = $nodo->adyacente('pasajeros');
            if (!$cont_pasajeros) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_pasajeros = $priv->adyacente('pasajeros');
            }
            $cont_cancelaciones = $nodo->adyacente('cancelaciones');
            if (!$cont_cancelaciones) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_cancelaciones = $priv->adyacente('cancelaciones');
            }

            foreach (array_keys($terminales_autorizados) as $nombre_terminal) {
                if ($terminal_filtro !== 'todos' && $nombre_terminal !== $terminal_filtro) continue;

                $enlace_compartido = 'compartido_con_' . $nombre_terminal;

                // Idempotencia: si ya existe, saltear.
                if ($nodo->adyacente($enlace_compartido)) {
                    $salteados++;
                    continue;
                }

                // Crear contenedor compartido.
                $compartido = Nodo::crear_con_dato($nombre_dueno);
                if (!$compartido) {
                    $errores[] = "No se pudo crear compartido para $nombre_dueno/$nombre_terminal";
                    continue;
                }
                $nodo->_adyacente_en($compartido, $enlace_compartido);

                // Sub-contenedores. `pasajeros` es alias, se enlaza después.
                $c_viajes = Nodo::crear_con_dato('');
                $c_empresas = Nodo::crear_con_dato('');
                $c_ventas = Nodo::crear_con_dato('');
                $c_cancelaciones = Nodo::crear_con_dato('');
                $c_terminales = Nodo::crear_con_dato('');

                $compartido->_adyacente_en($c_viajes, 'viajes');
                $compartido->_adyacente_en($c_empresas, 'empresas');
                $compartido->_adyacente_en($c_ventas, 'ventas');
                $compartido->_adyacente_en($c_cancelaciones, 'cancelaciones');
                $compartido->_adyacente_en($c_terminales, 'terminales');

                if ($cont_pasajeros) {
                    $compartido->_adyacente_en($cont_pasajeros, 'pasajeros');
                }

                // Llenar viajes + recolectar empresas referenciadas.
                $empresas_ref = [];
                foreach ($ady_viajes as $nv => $nodo_viaje) {
                    $tas = $nodo_viaje->adyacente('terminales_autorizadas');
                    if (!$tas || !$tas->adyacente($nombre_terminal)) continue;

                    $c_viajes->_adyacente_en($nodo_viaje, (string)$nv);

                    $micros = $nodo_viaje->adyacente('micros');
                    if ($micros) {
                        foreach ((array)$micros->adyacentes() as $nodo_micro) {
                            $emp = $nodo_micro->adyacente('empresa');
                            if ($emp) $empresas_ref[(string)$emp->dato()] = $emp;
                        }
                    }
                }

                // Llenar empresas.
                foreach ($empresas_ref as $ne => $nodo_e) {
                    $c_empresas->_adyacente_en($nodo_e, (string)$ne);
                }

                // Llenar ventas del terminal (árbol hmi/hd).
                if ($cont_ventas) {
                    $actual = hmi($cont_ventas);
                    $seg = 0;
                    while ($actual && $seg < 3000) {
                        $nt = $actual->adyacente('terminal');
                        if ($nt && $nt->dato() === $nombre_terminal) {
                            $c_ventas->_adyacente_en($actual, (string)$actual->id());
                        }
                        $actual = hd($actual);
                        $seg++;
                    }
                }

                // Llenar cancelaciones del terminal.
                if ($cont_cancelaciones) {
                    $actual = hmi($cont_cancelaciones);
                    $seg = 0;
                    while ($actual && $seg < 3000) {
                        $nt = $actual->adyacente('terminal');
                        if ($nt && $nt->dato() === $nombre_terminal) {
                            $c_cancelaciones->_adyacente_en($actual, (string)$actual->id());
                        }
                        $actual = hd($actual);
                        $seg++;
                    }
                }

                // Llenar terminales: solo el propio.
                $raiz = Nodo::nodo_por_id('usuarios');
                $nodo_term = $raiz ? $raiz->adyacente($nombre_terminal) : null;
                if ($nodo_term) {
                    $c_terminales->_adyacente_en($nodo_term, $nombre_terminal);
                }

                $creados++;
            }
            return null;
        }, null);

        return ['creados' => $creados, 'salteados' => $salteados, 'errores' => $errores];
    }, null, false);

    // ─── app:repuntar_terminales_compartido ─────────────
    //
    // Fase B2.3.5a del modelo topológico (v77f).
    //
    // Para cada dueño y cada uno de sus compartidos
    // `compartido_con_us_termX`:
    //   1. Marca el compartido con `dato = nombre_dueno` (opción A).
    //   2. Repunta el enlace `dueno` del terminal `us_termX` para
    //      que apunte al compartido en vez del nodo del dueño real.
    //
    // Después del repuntado, el terminal navega SOLO por el
    // subgrafo compartido. La app sigue funcionando igual porque
    // el código ya está preparado (B2.2 + B2.3.4).
    //
    // Idempotente: si el terminal ya apunta al compartido, se
    // saltea. Si el compartido ya tiene el dato correcto, también.
    //
    // Args: ['dueno' => nombre | 'todos',
    //        'terminal' => nombre | 'todos']
    // Devuelve: { repuntados: int, ya_repuntados: int, errores: [] }.
    Controlador::registrar_comando('app:repuntar_terminales_compartido', function(string $token, array $args) {
        $opciones = $args[0] ?? [];
        $dueno_filtro = (string)($opciones['dueno'] ?? 'todos');
        $terminal_filtro = (string)($opciones['terminal'] ?? 'todos');

        $repuntados = 0;
        $ya_repuntados = 0;
        $errores = [];

        Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$repuntados, &$ya_repuntados, &$errores, $dueno_filtro, $terminal_filtro) {
            $id = (string)$nodo->id();
            if (strpos($id, 'us_') !== 0) return null;

            // Nivel del usuario.
            $nivel_nodo = $nodo->adyacente('nivel');
            if (!$nivel_nodo) {
                $publico = $nodo->adyacente('publico');
                if ($publico) $nivel_nodo = $publico->adyacente('nivel');
            }
            $nivel = $nivel_nodo ? $nivel_nodo->dato() : '';
            if ($nivel !== 'dueno') return null;

            $nombre_dueno = (string)$nodo->dato();
            if ($dueno_filtro !== 'todos' && $nombre_dueno !== $dueno_filtro) return null;

            // Recorrer los compartidos del dueño.
            $ady = (array)$nodo->adyacentes();
            foreach ($ady as $enlace => $compartido) {
                $enlace = (string)$enlace;
                if (strpos($enlace, 'compartido_con_') !== 0) continue;

                $nombre_terminal = substr($enlace, strlen('compartido_con_'));
                if ($nombre_terminal === '') continue;
                if ($terminal_filtro !== 'todos' && $nombre_terminal !== $terminal_filtro) continue;

                // Debe estar marcado como compartido (por B2.3.3).
                if (!$compartido->adyacente('_es_compartido')) {
                    $errores[] = "Compartido $enlace no tiene _es_compartido. Correr B2.3.3 primero.";
                    continue;
                }

                // Encontrar el nodo del terminal.
                $nodo_terminal = Nodo::nodo_por_id('us_' . $nombre_terminal);
                if (!$nodo_terminal) {
                    $errores[] = "Terminal us_$nombre_terminal no encontrada para compartido $enlace.";
                    continue;
                }

                // Verificar si ya está repuntado.
                $nodo_dueno_actual = $nodo_terminal->adyacente('dueno');
                if ($nodo_dueno_actual && $nodo_dueno_actual->id() === $compartido->id()) {
                    // Ya repuntado. Pero por las dudas chequear el dato.
                    if ($compartido->dato() !== $nombre_dueno) {
                        $compartido->_dato($nombre_dueno);
                    }
                    $ya_repuntados++;
                    continue;
                }

                // 1. Marcar el compartido con el dato del dueño (opción A).
                $compartido->_dato($nombre_dueno);

                // 2. Repuntar el enlace `dueno` del terminal al compartido.
                $nodo_terminal->_adyacente_en($compartido, 'dueno', true);

                $repuntados++;
            }
            return null;
        }, null);

        return ['repuntados' => $repuntados, 'ya_repuntados' => $ya_repuntados, 'errores' => $errores];
    }, null, false);

    // ─── app:construir_arboles_compartidos ──────────────
    //
    // Fase B2.3.3 del modelo topológico (v76x).
    //
    // Convierte los compartidos creados en B2.1 (que tenían
    // enlaces planos por id) en árboles paralelos con nombres
    // parametrizados. Para cada compartido `compartido_con_us_termX`
    // de cada dueño:
    //   1. Marca el compartido con `_es_compartido` (testigo).
    //   2. Reconstruye el árbol del contenedor `ventas` con los
    //      nombres hmi_<term>, hd_<term>, p_<term>.
    //   3. Idem para `cancelaciones`.
    //
    // Los nodos venta y cancelación son los MISMOS que usa el
    // dueño: se insertan también en el árbol del compartido con
    // los nombres parametrizados. Cada venta pertenece a 2
    // árboles (el del dueño y el del terminal que la vendió).
    //
    // Idempotente: si un compartido ya tiene `_es_compartido`,
    // se saltea.
    //
    // Args: ['dueno' => nombre | 'todos',
    //        'terminal' => nombre | 'todos']
    // Devuelve: { marcados: int, saltados: int, errores: [] }.
    Controlador::registrar_comando('app:construir_arboles_compartidos', function(string $token, array $args) {
        $opciones = $args[0] ?? [];
        $dueno_filtro = (string)($opciones['dueno'] ?? 'todos');
        $terminal_filtro = (string)($opciones['terminal'] ?? 'todos');

        $marcados = 0;
        $saltados = 0;
        $errores = [];

        Nodo::por_cada_nodo_ejecutar($token, function($nodo) use (&$marcados, &$saltados, &$errores, $dueno_filtro, $terminal_filtro) {
            $id = (string)$nodo->id();
            if (strpos($id, 'us_') !== 0) return null;

            // Nivel del usuario: puede estar en la raíz o dentro de `publico`.
            $nivel_nodo = $nodo->adyacente('nivel');
            if (!$nivel_nodo) {
                $publico = $nodo->adyacente('publico');
                if ($publico) $nivel_nodo = $publico->adyacente('nivel');
            }
            $nivel = $nivel_nodo ? $nivel_nodo->dato() : '';
            if ($nivel !== 'dueno') return null;

            $nombre_dueno = (string)$nodo->dato();
            if ($dueno_filtro !== 'todos' && $nombre_dueno !== $dueno_filtro) return null;

            // Contenedor de ventas del dueño (para iterar el árbol default).
            $cont_ventas_privado = $nodo->adyacente('ventas');
            if (!$cont_ventas_privado) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_ventas_privado = $priv->adyacente('ventas');
            }
            // Contenedor de cancelaciones del dueño.
            $cont_cancel_privado = $nodo->adyacente('cancelaciones');
            if (!$cont_cancel_privado) {
                $priv = $nodo->adyacente('privado');
                if ($priv) $cont_cancel_privado = $priv->adyacente('cancelaciones');
            }

            // Recorrer todos los compartidos del dueño.
            $ady = (array)$nodo->adyacentes();
            foreach ($ady as $enlace => $hijo) {
                $enlace = (string)$enlace;
                if (strpos($enlace, 'compartido_con_') !== 0) continue;

                $nombre_terminal = substr($enlace, strlen('compartido_con_'));
                if ($nombre_terminal === '') continue;
                if ($terminal_filtro !== 'todos' && $nombre_terminal !== $terminal_filtro) continue;

                $compartido = $hijo;

                // Idempotencia: si ya está marcado, saltear.
                if ($compartido->adyacente('_es_compartido')) {
                    $saltados++;
                    continue;
                }

                // 1. Marcar el compartido.
                $compartido->_adyacente_en(Nodo::crear_con_dato('1'), '_es_compartido');

                $nombres = [
                    'p'   => 'p_'   . $nombre_terminal,
                    'hd'  => 'hd_'  . $nombre_terminal,
                    'hmi' => 'hmi_' . $nombre_terminal,
                ];

                // 2. Árbol de ventas del compartido.
                $c_ventas = $compartido->adyacente('ventas');
                if ($c_ventas) {
                    // Borrar todos los enlaces del contenedor (planos de B2.1).
                    $enlaces_viejos = (array)$c_ventas->adyacentes();
                    foreach ($enlaces_viejos as $e => $_) {
                        $c_ventas->eliminar_adyacente((string)$e);
                    }

                    // Recolectar las ventas del terminal en el orden del árbol del dueño.
                    $ventas_term = [];
                    if ($cont_ventas_privado) {
                        $venta = hmi($cont_ventas_privado);
                        $seg = 0;
                        while ($venta && $seg < 3000) {
                            $nt = $venta->adyacente('terminal');
                            if ($nt && $nt->dato() === $nombre_terminal) {
                                $ventas_term[] = $venta;
                            }
                            $venta = hd($venta);
                            $seg++;
                        }
                    }

                    // Insertar en el árbol del compartido en orden inverso
                    // con _hmi (que agrega al inicio) para preservar el orden.
                    for ($i = count($ventas_term) - 1; $i >= 0; $i--) {
                        _hmi($c_ventas, $ventas_term[$i], $nombres);
                    }
                }

                // 3. Idem para cancelaciones.
                $c_cancel = $compartido->adyacente('cancelaciones');
                if ($c_cancel) {
                    $enlaces_viejos = (array)$c_cancel->adyacentes();
                    foreach ($enlaces_viejos as $e => $_) {
                        $c_cancel->eliminar_adyacente((string)$e);
                    }

                    $cancel_term = [];
                    if ($cont_cancel_privado) {
                        $cancel = hmi($cont_cancel_privado);
                        $seg = 0;
                        while ($cancel && $seg < 3000) {
                            $nt = $cancel->adyacente('terminal');
                            if ($nt && $nt->dato() === $nombre_terminal) {
                                $cancel_term[] = $cancel;
                            }
                            $cancel = hd($cancel);
                            $seg++;
                        }
                    }

                    for ($i = count($cancel_term) - 1; $i >= 0; $i--) {
                        _hmi($c_cancel, $cancel_term[$i], $nombres);
                    }
                }

                $marcados++;
            }
            return null;
        }, null);

        return ['marcados' => $marcados, 'saltados' => $saltados, 'errores' => $errores];
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