<?php
/**
 * Funciones auxiliares de formato, validación y persistencia.
 *
 * @package   Iteradores
 * @since     1.5piloto.37
 * @version   1.5piloto.74y
 */

use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Nodos\Nodo;
include_once(__DIR__ . '/../Configuracion/Configuracion.php');
include_once(__DIR__ . '/../Controlador/Controlador.php');
include_once(__DIR__ . '/../Nodos/Nodo.php');

/**
 * Normaliza un DNI: devuelve solo los dígitos.
 * Ej: "30.309.409" -> "30309409"
 */
function normalizar_dni(string $dni): string {
    return preg_replace('/\D+/', '', $dni);
}

/**
 * Formatea un DNI con puntos al estilo argentino.
 * - 6 dígitos: XX.XXX
 * - 7 dígitos: X.XXX.XXX
 * - 8 dígitos: XX.XXX.XXX
 * Si tiene otra cantidad de dígitos, lo devuelve tal cual.
 */
function formatear_dni_con_puntos(string $dni): string {
    $solo_digitos = normalizar_dni($dni);
    $len = strlen($solo_digitos);
    if ($len === 6) {
        return substr($solo_digitos, 0, 2) . '.' . substr($solo_digitos, 2, 3);
    }
    if ($len === 7) {
        return substr($solo_digitos, 0, 1) . '.' . substr($solo_digitos, 1, 3) . '.' . substr($solo_digitos, 4, 3);
    }
    if ($len === 8) {
        return substr($solo_digitos, 0, 2) . '.' . substr($solo_digitos, 2, 3) . '.' . substr($solo_digitos, 5, 3);
    }
    return $dni;
}

/**
 * Formatea una fecha ISO (YYYY-MM-DD) a formato visible (DD/MM/YYYY).
 * Si ya viene con '/', la devuelve tal cual.
 * Si es 'a confirmar' o vacía, la devuelve tal cual.
 */
function formatear_fecha_visible(string $fecha): string {
    $fecha = trim($fecha);
    if ($fecha === '' || $fecha === 'a confirmar') return $fecha;
    if (strpos($fecha, '/') !== false) return $fecha;
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    return $fecha;
}

// ============================================================
// Validaciones. Todas devuelven null si OK, o string con el error.
// ============================================================

/**
 * Valida un DNI. Solo dígitos, entre 6 y 8 caracteres.
 */
function validar_dni(string $dni): ?string {
    $dni = trim($dni);
    if ($dni === '') return 'El DNI es obligatorio';
    if (!preg_match('/^\d+$/', $dni)) return 'El DNI solo puede tener números';
    $len = strlen($dni);
    if ($len < 6 || $len > 8) return 'El DNI debe tener entre 6 y 8 números';
    return null;
}

/**
 * Valida un teléfono. Opcional '+' al inicio, después solo dígitos.
 * Cantidad de dígitos: entre 10 y 15.
 */
function validar_telefono(string $tel): ?string {
    $tel = trim($tel);
    if ($tel === '') return 'El teléfono es obligatorio';
    if (strpos($tel, '+') === 0) {
        $cuerpo = substr($tel, 1);
    } else {
        $cuerpo = $tel;
    }
    if (!preg_match('/^\d+$/', $cuerpo)) {
        return 'El teléfono solo puede tener números, o empezar con +';
    }
    $len = strlen($cuerpo);
    if ($len < 10 || $len > 15) {
        return 'El teléfono debe tener entre 10 y 15 números';
    }
    return null;
}

/**
 * Valida un email. Formato básico usuario@dominio.ext (permite subdominios y TLDs compuestos).
 * Es opcional: si viene vacío, no hay error.
 */
function validar_email(string $email): ?string {
    $email = trim($email);
    if ($email === '') return null;
    if (strlen($email) > 100) return 'El email no puede tener más de 100 caracteres';
    if (!preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $email)) {
        return 'El email no parece válido';
    }
    return null;
}

/**
 * Valida apellido o nombres. Letras (incluye tildes, ñ, ü), espacios, apóstrofes y guiones.
 * Longitud 1 a 60.
 */
function validar_nombre_o_apellido(string $valor): ?string {
    $valor = trim($valor);
    if ($valor === '') return 'Este campo es obligatorio';
    if (strlen($valor) > 60) return 'No puede tener más de 60 caracteres';
    if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\'\-\s]+$/u', $valor)) {
        return 'Solo puede tener letras, espacios, apóstrofes y guiones';
    }
    return null;
}

/**
 * Valida una fecha de nacimiento. Formato YYYY-MM-DD.
 * No puede ser futura. Edad entre 0 y 120.
 */
function validar_fecha_nacimiento(string $fecha): ?string {
    $fecha = trim($fecha);
    if ($fecha === '') return 'La fecha de nacimiento es obligatoria';
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
        return 'La fecha de nacimiento no es válida';
    }
    $anio = (int)$m[1]; $mes = (int)$m[2]; $dia = (int)$m[3];
    if (!checkdate($mes, $dia, $anio)) {
        return 'La fecha de nacimiento no es válida';
    }
    $fecha_nac = new DateTime($fecha);
    $hoy = new DateTime();
    if ($fecha_nac > $hoy) return 'La fecha de nacimiento no puede ser futura';
    $edad = $hoy->diff($fecha_nac)->y;
    if ($edad > 120) return 'La fecha de nacimiento no parece válida';
    return null;
}

/**
 * Valida una localidad. Letras, números, espacios, guiones y apóstrofes.
 */
function validar_localidad(string $valor): ?string {
    $valor = trim($valor);
    if ($valor === '') return 'La localidad es obligatoria';
    if (strlen($valor) > 80) return 'La localidad no puede tener más de 80 caracteres';
    if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\'\-\s]+$/u', $valor)) {
        return 'La localidad solo puede tener letras, números, espacios, apóstrofes y guiones';
    }
    return null;
}

/**
 * Valida una dirección. Letras, números, espacios, comas, puntos, grados, guiones, apóstrofes.
 */
function validar_direccion(string $valor): ?string {
    $valor = trim($valor);
    if ($valor === '') return 'La dirección es obligatoria';
    if (strlen($valor) > 120) return 'La dirección no puede tener más de 120 caracteres';
    if (!preg_match('/^[A-Za-z0-9ÁÉÍÓÚáéíóúÑÑÜü\'\-\s,\.°º]+$/u', $valor)) {
        return 'La dirección tiene caracteres no permitidos';
    }
    return null;
}

// ============================================================
// Destrucción progresiva (Fase 2 del plan de optimización del grafo)
// ============================================================

/**
 * Desenlaza y destruye los adyacentes de $padre que sean
 * "campos simples": nodos sin adyacentes propios. No toca a
 * los que sí tienen adyacentes (estructuras).
 *
 * Se usa desde los helpers de destrucción del piloto (por
 * ejemplo _destruir_viaje_completo en Viaje.php y
 * cancelar_venta en Venta.php). Se movió acá en v74v para
 * que Venta.php la pueda usar sin depender de Viaje.php.
 *
 * @param Nodo  $padre
 * @param array $excluir_enlaces Enlaces a NO tocar (referencias
 *                               externas o circulares).
 */
function _destruir_campos_simples(Nodo $padre, array $excluir_enlaces = []): void {
    $adyacentes = (array) $padre->adyacentes();
    foreach ($adyacentes as $enlace => $nodo_hijo) {
        $enlace = (string)$enlace;
        if (in_array($enlace, $excluir_enlaces, true)) continue;
        // Solo destruir si el hijo no tiene adyacentes propios.
        $hijos_del_hijo = (array) $nodo_hijo->adyacentes();
        if (!empty($hijos_del_hijo)) continue;

        $padre->eliminar_adyacente($enlace);
        Nodo::eliminar($nodo_hijo);
    }
}

/**
 * Destruye la lista circular de asientos de un piso.
 *
 * Recolecta los asientos (todos menos la cabeza), rompe el
 * círculo desenlazando el `siguiente` de todos, desenlaza
 * el `primer` de la cabeza, desenlaza la cabeza del piso,
 * y destruye cada asiento con sus campos. Al final destruye
 * la cabeza.
 *
 * Se usa desde _destruir_piso, que a su vez se usa desde
 * _destruir_vehiculo_completo. Movido de Viaje.php a
 * FuncionesAuxiliares.php en v74y para que Vehiculo.php
 * y Empresa.php lo puedan usar sin depender de Viaje.php.
 *
 * @param Nodo $nodo_piso
 */
function _destruir_lista_circular_asientos(Nodo $nodo_piso): void {
    $cabeza = $nodo_piso->adyacente('asientos');
    if (!$cabeza) return;

    // Recolectar todos los asientos menos la cabeza.
    $asientos = [];
    $actual = $cabeza->adyacente('primer');
    $seg = 0;
    while ($actual && $actual->id() !== $cabeza->id() && $seg < 1000) {
        $asientos[] = $actual;
        $actual = $actual->adyacente('siguiente');
        $seg++;
    }

    // Desenlazar el `siguiente` de TODOS los asientos.
    foreach ($asientos as $a) {
        $a->eliminar_adyacente('siguiente');
    }

    // Desenlazar el `primer` de la cabeza.
    $cabeza->eliminar_adyacente('primer');

    // Desenlazar la cabeza del piso.
    $nodo_piso->eliminar_adyacente('asientos');

    // Destruir cada asiento con sus campos. Las referencias
    // externas (pasajero, venta) solo se desenlazan.
    foreach ($asientos as $asiento) {
        _destruir_campos_simples($asiento, ['pasajero', 'venta']);
        $asiento->eliminar_adyacente('pasajero');
        $asiento->eliminar_adyacente('venta');
        Nodo::eliminar($asiento);
    }

    // Destruir la cabeza.
    _destruir_campos_simples($cabeza);
    Nodo::eliminar($cabeza);
}

/**
 * Destruye un piso: la lista circular de asientos, el nodo
 * cabeza, y los campos filas/columnas.
 *
 * @param Nodo $nodo_piso
 */
function _destruir_piso(Nodo $nodo_piso): void {
    _destruir_lista_circular_asientos($nodo_piso);
    _destruir_campos_simples($nodo_piso);
    Nodo::eliminar($nodo_piso);
}

/**
 * Destruye un vehículo completo: sus pisos, el contenedor de
 * asientos, y el nodo vehículo con sus campos (nombre, foto).
 *
 * Se usa tanto para el vehículo original (empresa) como para
 * la copia que se clona en un micro. La estructura es la
 * misma en los dos casos.
 *
 * Antes de llamar a esta función, el llamador debe desenlazar
 * el vehículo del contenedor que lo referencia.
 *
 * Movido de Viaje.php a FuncionesAuxiliares.php en v74y, y
 * renombrado de _destruir_copia_vehiculo a
 * _destruir_vehiculo_completo.
 *
 * @param Nodo $nodo_vehiculo
 */
function _destruir_vehiculo_completo(Nodo $nodo_vehiculo): void {
    $nodo_asientos = $nodo_vehiculo->adyacente('asientos');
    if ($nodo_asientos) {
        for ($i = 1; $i <= 2; $i++) {
            $piso = $nodo_asientos->adyacente("piso_$i");
            if ($piso) {
                // Desenlazar PRIMERO: el contenedor apunta al
                // piso con `piso_$i`. Si no se desenlaza antes
                // de destruir el piso, el piso tiene una
                // referencia entrante y Nodo::eliminar falla.
                $nodo_asientos->eliminar_adyacente("piso_$i");
                _destruir_piso($piso);
            }
        }
        $nodo_vehiculo->eliminar_adyacente('asientos');
        _destruir_campos_simples($nodo_asientos);
        Nodo::eliminar($nodo_asientos);
    }
    _destruir_campos_simples($nodo_vehiculo);
    Nodo::eliminar($nodo_vehiculo);
}

// ============================================================
// Persistencia
// ============================================================

/**
 * Guarda una superestructura solo en SQL.
 *
 * Hasta v74j esta función también escribía un respaldo JSON
 * automático (json_encode de todo el grafo en cada guardado).
 * Cuando el grafo creció (varias terminales, vehículos, ventas,
 * cupones), ese encode se volvió el cuello de botella: cada
 * operación tardaba segundos y rompía los timeouts de las
 * pruebas del plugin. Desde v74m se eliminó.
 *
 * La implementación `PerdurarSuperestructuraStringJSON` sigue
 * disponible en el framework. El respaldo en formatos
 * alternativos pasa a ser una acción manual del admin, a
 * implementar en el rediseño del panel.
 *
 * @param string $nombre Nombre de la superestructura.
 * @return bool True si el guardado en SQL fue exitoso.
 */
function guardar_ambos($nombre): bool {
    if (!is_string($nombre) || $nombre === '') {
        Controlador::_error("guardar_ambos: nombre invalido");
        return false;
    }

    // Defensa: no guardar si la superestructura esta vacia.
    // Guardar vacio pisa el grafo con nada.
    if (!Nodo::hay_nodos_en_superestructura()) {
        Controlador::_error("guardar_ambos: superestructura vacia para \"$nombre\". Se aborta para no pisar el grafo.");
        return false;
    }

    // Guardar en SQL (única fuente de verdad).
    return (bool) Controlador::guardar($nombre);
}