<?php
/**
 * Gestión de vehículos de empresas.
 *
 * @package   Iteradores
 * @since     1.5piloto.5
 * @version   1.5piloto.7
 */

use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
include_once("./Configuracion/Configuracion.php");
include_once("./Nodos/Nodo.php");
include_once("./Controlador/Controlador.php");

/**
 * Lista los vehículos asociados a una empresa.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @return array Lista de vehículos con su información.
 */
function listar_vehiculos_de_empresa(string $nombre_empresa): array {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return [];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) return [];

        $adyacentes = $nodo_vehiculos->adyacentes();
        if (!$adyacentes) return [];

        $vehiculos = [];
        foreach ($adyacentes as $nombre_vehiculo => $nodo_vehiculo) {
            $nodo_nombre = $nodo_vehiculo->adyacente('nombre');
            $nodo_asientos = $nodo_vehiculo->adyacente('asientos');
            $asientos = $nodo_asientos ? $nodo_asientos->dato() : '0';

            // Foto
            $nodo_foto = $nodo_vehiculo->adyacente('foto');
            $foto = $nodo_foto ? $nodo_foto->dato() : '';

            // Configuración
            $configuracion = ['pisos' => []];
            if ($nodo_asientos) {
                for ($i = 1; $i <= 2; $i++) {
                    $piso = $nodo_asientos->adyacente("piso_$i");
                    if ($piso) {
                        $config_piso = obtener_configuracion_piso($piso);
                        if ($config_piso) {
                            $configuracion['pisos'][] = $config_piso;
                        }
                    }
                }
            }

            $vehiculos[] = [
                'nombre_vehiculo' => $nombre_vehiculo,
                'nombre' => $nodo_nombre ? $nodo_nombre->dato() : $nombre_vehiculo,
                'asientos' => $asientos,
                'foto' => $foto,
                'configuracion' => $configuracion
            ];
        }
        return $vehiculos;
    }
    return [];
}

/**
 * Obtiene la configuración de un piso a partir de su nodo.
 *
 * @param Nodo $nodo_piso Nodo del piso.
 * @return array|null Arreglo con filas, columnas y asientos, o null si no hay datos.
 */
function obtener_configuracion_piso($nodo_piso): ?array {
    $nodo_filas = $nodo_piso->adyacente('filas');
    $nodo_columnas = $nodo_piso->adyacente('columnas');
    if (!$nodo_filas || !$nodo_columnas) return null;

    $filas = (int)$nodo_filas->dato();
    $columnas = (int)$nodo_columnas->dato();

    $asientos = [];
    $nodo_cabeza = $nodo_piso->adyacente('asientos');
    if ($nodo_cabeza) {
        $actual = $nodo_cabeza->adyacente('primer');
        $contador_seguridad = 0;
        while ($actual && $actual->id() !== $nodo_cabeza->id() && $contador_seguridad < 1000) {
            $fila = $actual->adyacente('fila');
            $columna = $actual->adyacente('columna');
            if ($fila && $columna) {
                $asientos[] = [
                    'fila' => $fila->dato(),
                    'columna' => $columna->dato(),
                    'numero' => $actual->dato()
                ];
            }
            $actual = $actual->adyacente('siguiente');
            $contador_seguridad++;
        }
    }

    return [
        'filas' => $filas,
        'columnas' => $columnas,
        'asientos' => $asientos
    ];
}

/**
 * Agrega un nuevo vehículo a una empresa.
 * Ya no recibe cantidad de asientos; se crea con asientos=0 y sin configuración.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_vehiculo Nombre identificador del vehículo (patente).
 * @param string $nombre_real Nombre visible del vehículo.
 * @return array Resultado de la operación.
 */
function agregar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, string $nombre_real = ''): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'El nombre del vehículo es obligatorio'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) {
            $nodo_vehiculos = Nodo::crear_con_dato('');
            $nodo_empresa->_adyacente_en($nodo_vehiculos, 'vehiculos');
        }

        if ($nodo_vehiculos->adyacente($nombre_vehiculo)) {
            return ['exito' => false, 'error' => 'Ya existe un vehículo con ese nombre'];
        }

        $nodo_vehiculo = Nodo::crear_con_dato($nombre_vehiculo);
        $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real ?: $nombre_vehiculo), 'nombre');
        $nodo_asientos = Nodo::crear_con_dato('0');
        $nodo_vehiculo->_adyacente_en($nodo_asientos, 'asientos');

        $nodo_vehiculos->_adyacente_en($nodo_vehiculo, $nombre_vehiculo);

        Controlador::guardar(Conf::NOMBRE_APP);

        return ['exito' => true];
    }
    return ['exito' => false, 'error' => 'Empresa no encontrada'];
}

/**
 * Actualiza los datos básicos de un vehículo (nombre visible y cantidad de asientos simple).
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_vehiculo Identificador del vehículo (patente).
 * @param array $datos Datos a actualizar. Claves: 'nombre_real', 'asientos'.
 * @return array Resultado.
 */
function actualizar_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $datos): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'El identificador del vehículo es obligatorio'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    }

    // Buscar la empresa globalmente
    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) {
            return ['exito' => false, 'error' => 'La empresa no tiene vehículos registrados'];
        }

        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);
        if (!$nodo_vehiculo) {
            return ['exito' => false, 'error' => 'Vehículo no encontrado en esta empresa'];
        }

        // Actualizar nombre visible
        if (array_key_exists('nombre_real', $datos)) {
            $nombre_real = trim($datos['nombre_real']);
            if (empty($nombre_real)) {
                $nombre_real = $nombre_vehiculo;
            }
            $nodo_nombre = $nodo_vehiculo->adyacente('nombre');
            if ($nodo_nombre) {
                $nodo_nombre->_dato($nombre_real);
            } else {
                $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato($nombre_real), 'nombre');
            }
        }

        // Actualizar cantidad de asientos
        if (array_key_exists('asientos', $datos)) {
            $asientos = (int)$datos['asientos'];
            if ($asientos <= 0) {
                return ['exito' => false, 'error' => 'La cantidad de asientos debe ser mayor que cero'];
            }
            $nodo_asientos = $nodo_vehiculo->adyacente('asientos');
            if ($nodo_asientos) {
                $nodo_asientos->_dato((string)$asientos);
            } else {
                $nodo_vehiculo->_adyacente_en(Nodo::crear_con_dato((string)$asientos), 'asientos');
            }
        }

        Controlador::guardar(Conf::NOMBRE_APP);

        return ['exito' => true];
    }

    return ['exito' => false, 'error' => 'Empresa no encontrada'];
}
/**
 * Actualiza la configuración física completa de un vehículo.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_vehiculo Identificador del vehículo (patente).
 * @param array $configuracion Arreglo con 'pisos' => [ ['filas'=>int, 'columnas'=>int, 'asientos'=>[ ['fila'=>string, 'columna'=>string, 'numero'=>string] ] ], ... ].
 * @return array Resultado.
 */
function actualizar_configuracion_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $configuracion): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'El identificador del vehículo es obligatorio'];
    }

    if (!isset($configuracion['pisos']) || !is_array($configuracion['pisos'])) {
        return ['exito' => false, 'error' => 'Configuración inválida'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    }

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) {
            return ['exito' => false, 'error' => 'La empresa no tiene vehículos'];
        }

        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);
        if (!$nodo_vehiculo) {
            return ['exito' => false, 'error' => 'Vehículo no encontrado'];
        }

        $nodo_asientos = $nodo_vehiculo->adyacente('asientos');
        if (!$nodo_asientos) {
            $nodo_asientos = Nodo::crear_con_dato('0');
            $nodo_vehiculo->_adyacente_en($nodo_asientos, 'asientos');
        }

        // Eliminar pisos anteriores
        $nodo_asientos->eliminar_adyacente('piso_1');
        $nodo_asientos->eliminar_adyacente('piso_2');

        $total_asientos = 0;
        $indice_piso = 1;
        foreach ($configuracion['pisos'] as $piso_datos) {
            if (!isset($piso_datos['filas'], $piso_datos['columnas'], $piso_datos['asientos'])) {
                continue;
            }
            $filas = (int)$piso_datos['filas'];
            $columnas = (int)$piso_datos['columnas'];
            if ($filas <= 0 || $columnas <= 0) continue;

            $nodo_piso = Nodo::crear_con_dato('');
            $nodo_piso->_adyacente_en(Nodo::crear_con_dato((string)$filas), 'filas');
            $nodo_piso->_adyacente_en(Nodo::crear_con_dato((string)$columnas), 'columnas');

            $nodo_cabeza = Nodo::crear_con_dato('');
            $nodo_piso->_adyacente_en($nodo_cabeza, 'asientos');

            $primer = null;
            $anterior = null;
            foreach ($piso_datos['asientos'] as $asiento_datos) {
                if (!isset($asiento_datos['fila'], $asiento_datos['columna'], $asiento_datos['numero'])) continue;
                $fila = (string)$asiento_datos['fila'];
                $columna = (string)$asiento_datos['columna'];
                $numero = (string)$asiento_datos['numero'];

                $nodo_asiento = Nodo::crear_con_dato($numero);
                $nodo_asiento->_adyacente_en(Nodo::crear_con_dato($fila), 'fila');
                $nodo_asiento->_adyacente_en(Nodo::crear_con_dato($columna), 'columna');

                if ($anterior) {
                    $anterior->_adyacente_en($nodo_asiento, 'siguiente');
                } else {
                    $primer = $nodo_asiento;
                }
                $anterior = $nodo_asiento;
                $total_asientos++;
            }

            if ($anterior) {
                $anterior->_adyacente_en($nodo_cabeza, 'siguiente');
            }
            if ($primer) {
                $nodo_cabeza->_adyacente_en($primer, 'primer');
            }

            $nodo_asientos->_adyacente_en($nodo_piso, 'piso_' . $indice_piso);
            $indice_piso++;
        }

        $nodo_asientos->_dato((string)$total_asientos);

        Controlador::guardar(Conf::NOMBRE_APP);

        return ['exito' => true];
    }

    return ['exito' => false, 'error' => 'Empresa no encontrada'];
}

/**
 * Elimina un vehículo de la empresa.
 *
 * @param string $nombre_empresa Nombre identificador de la empresa.
 * @param string $nombre_vehiculo Identificador del vehículo (patente).
 * @return array Resultado.
 */
function eliminar_vehiculo(string $nombre_empresa, string $nombre_vehiculo): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'Identificador de vehículo obligatorio'];
    }

    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) return ['exito' => false, 'error' => 'No hay usuarios registrados'];

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;
        $nodo_empresa = $nodo_empresas->adyacente($nombre_empresa);
        if (!$nodo_empresa) continue;

        $nodo_vehiculos = $nodo_empresa->adyacente('vehiculos');
        if (!$nodo_vehiculos) return ['exito' => false, 'error' => 'La empresa no tiene vehículos'];

        $nodo_vehiculo = $nodo_vehiculos->adyacente($nombre_vehiculo);
        if (!$nodo_vehiculo) return ['exito' => false, 'error' => 'Vehículo no encontrado'];

        // TODO: Aquí falta eliminar nodos huérfanos (pisos, asientos, foto, etc.).
        // Por ahora solo se elimina el enlace.
        $nodo_vehiculos->eliminar_adyacente($nombre_vehiculo);

        Controlador::guardar(Conf::NOMBRE_APP);
        return ['exito' => true];
    }

    return ['exito' => false, 'error' => 'Empresa no encontrada'];
}

/**
 * Sube una foto para un vehículo y la asocia al nodo.
 *
 * @param string $nombre_empresa Nombre de la empresa.
 * @param string $nombre_vehiculo Identificador del vehículo.
 * @param array $archivo Arreglo $_FILES['foto'].
 * @return array Resultado con la ruta de la foto si éxito.
 */
function subir_foto_vehiculo(string $nombre_empresa, string $nombre_vehiculo, array $archivo): array {
    $nombre_vehiculo = trim($nombre_vehiculo);
    if (empty($nombre_vehiculo)) {
        return ['exito' => false, 'error' => 'Identificador de vehículo obligatorio'];
    }

    if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
        return ['exito' => false, 'error' => 'No se recibió archivo válido'];
    }

    // Validar extensión
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $permitidas)) {
        return ['exito' => false, 'error' => 'Formato de imagen no permitido'];
    }

    // Crear carpeta si no existe
    $directorio = __DIR__ . '/../../uploads/vehiculos/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    // Nombre único: patente + timestamp + extensión
    $nombre_archivo = $nombre_vehiculo . '_' . time() . '.' . $extension;
    $ruta_destino = $directorio . $nombre_archivo;
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return ['exito' => false, 'error' => 'Error al guardar la imagen'];
    }

    // Ruta relativa desde la raíz del proyecto (index.php)
    $ruta_relativa = 'uploads/vehiculos/' . $nombre_archivo;

    // Buscar vehículo y guardar enlace (insensible a mayúsculas/minúsculas)
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    if (!$raiz_usuarios) {
        unlink($ruta_destino);
        return ['exito' => false, 'error' => 'No hay usuarios registrados'];
    }

    foreach ($raiz_usuarios->adyacentes() as $nombre_dueno => $nodo_dueno) {
        $nodo_empresas = $nodo_dueno->adyacente('empresas');
        if (!$nodo_empresas) continue;

        // Buscar empresa comparando sin mayúsculas
        $empresa_encontrada = null;
        foreach ($nodo_empresas->adyacentes() as $nombre_empresa_actual => $nodo_empresa_actual) {
            if (strcasecmp($nombre_empresa_actual, $nombre_empresa) === 0) {
                $empresa_encontrada = $nodo_empresa_actual;
                $nombre_empresa = $nombre_empresa_actual; // usar el original
                break;
            }
        }
        if (!$empresa_encontrada) continue;

        $nodo_vehiculos = $empresa_encontrada->adyacente('vehiculos');
        if (!$nodo_vehiculos) continue;

        // Buscar vehículo comparando sin mayúsculas
        $vehiculo_encontrado = null;
        foreach ($nodo_vehiculos->adyacentes() as $nombre_vehiculo_actual => $nodo_vehiculo_actual) {
            if (strcasecmp($nombre_vehiculo_actual, $nombre_vehiculo) === 0) {
                $vehiculo_encontrado = $nodo_vehiculo_actual;
                $nombre_vehiculo = $nombre_vehiculo_actual; // usar el original
                break;
            }
        }
        if (!$vehiculo_encontrado) continue;

        // Eliminar foto anterior si existe
        $foto_anterior = $vehiculo_encontrado->adyacente('foto');
        if ($foto_anterior) {
            $ruta_anterior = __DIR__ . '/../../' . $foto_anterior->dato();
            if (file_exists($ruta_anterior)) {
                unlink($ruta_anterior);
            }
            $vehiculo_encontrado->eliminar_adyacente('foto');
        }

        // Crear enlace foto
        $vehiculo_encontrado->_adyacente_en(Nodo::crear_con_dato($ruta_relativa), 'foto');

        // Guardar persistencia
        Controlador::guardar(Conf::NOMBRE_APP);

        return ['exito' => true, 'foto' => $ruta_relativa];
    }

    // Si no encontró vehículo, eliminar archivo
    unlink($ruta_destino);
    return ['exito' => false, 'error' => 'Vehículo no encontrado'];
}