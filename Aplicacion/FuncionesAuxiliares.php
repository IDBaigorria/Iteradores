<?php
/**
 * Funciones auxiliares de formato y validación.
 *
 * @package   Iteradores
 * @since     1.5piloto.37
 * @version   1.5piloto.37
 */

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