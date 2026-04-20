<?php
/**
 * Genera un UUID versión 4 (RFC 4122).
 *
 * Intenta usar fuentes criptográficamente seguras como `random_bytes()`
 * o `openssl_random_pseudo_bytes()` si están disponibles. En caso contrario,
 * recurre a `mt_rand()` como fallback (no apto para seguridad).
 *
 * @return string UUID v4 en formato estándar (ej. "3f9a1b70-9210-4ac9-b3c2-71a27b5bdf83")
 *
 * @example
 * $id = generarUUID();
 * echo $id; // "e5b0d6f9-20b4-4a93-99f0-21880e78e2b0"
 *
 * @note
 * Si se usa `random_bytes()` u `openssl_random_pseudo_bytes()`, el UUID
 * es criptográficamente seguro. El fallback con `mt_rand()` solo debe
 * emplearse para propósitos no críticos (identificadores internos, etc.).
 */
function generarUUID(): string {
    // Fuente principal: random_bytes (PHP 7+)
    if (function_exists('random_bytes')) {
        $data = random_bytes(16);
    }
    // Alternativa segura: OpenSSL
    elseif (function_exists('openssl_random_pseudo_bytes')) {
        $data = openssl_random_pseudo_bytes(16);
    }
    // Último recurso: mt_rand (no criptográficamente seguro)
    else {
        $data = '';
        for ($i = 0; $i < 16; $i++) {
            $data .= chr(mt_rand(0, 255));
        }
    }

    // Ajuste de bits según RFC 4122 versión 4
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // versión 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variante RFC 4122

    // Formato estándar 8-4-4-4-12
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
