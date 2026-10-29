<?php
/**
 * Log de persistencia del framework Iteradores.
 *
 * Escribe a un archivo del proyecto (bajo logs/persistencia/)
 * en lugar de (o además de) error_log. Pensado para hostings
 * compartidos donde el error.log de PHP no es accesible.
 *
 * Activación: Conf::DEBUG_PERSISTENCIA = true.
 * Los mensajes con nivel 'error' se escriben SIEMPRE, aunque
 * DEBUG_PERSISTENCIA esté en false (los errores no se pierden).
 *
 * @author  Ignacio David Baigorria
 * @package Iteradores\Configuracion
 * @since   1.5i.7w
 * @version 1.5i.7w
 */

use Iteradores\Configuracion\Conf;

require_once __DIR__ . '/Configuracion.php';

/**
 * Escribe un mensaje al log de persistencia del proyecto.
 *
 * @param string $msg   Mensaje.
 * @param string $nivel 'info' | 'warn' | 'error'. Los 'error'
 *                      se escriben siempre; 'info'/'warn' solo
 *                      si Conf::DEBUG_PERSISTENCIA está activo.
 * @return void
 */
function log_persistencia(string $msg, string $nivel = 'info'): void {
    $nivel = strtolower($nivel);
    if (!in_array($nivel, ['info', 'warn', 'error'], true)) $nivel = 'info';
    if (($nivel === 'info' || $nivel === 'warn') && !Conf::DEBUG_PERSISTENCIA) return;

    $dir = _log_persistencia_directorio();
    if ($dir === null) {
        // Fallback: si no se pudo crear el directorio, error_log.
        error_log("[LOG_PERSISTENCIA] " . $msg);
        return;
    }

    $fecha = date('Y-m-d');
    $ruta = $dir . $fecha . '.log';
    $primera_vez_hoy = !file_exists($ruta);
    $linea = '[' . date('Y-m-d H:i:s') . '] [' . $nivel . '] ' . $msg . "\n";

    // Rotación: si el archivo del día supera el máximo,
    // se trunca al 50% (deja las líneas más recientes).
    if (file_exists($ruta)) {
        $tam = @filesize($ruta);
        if ($tam !== false && $tam > Conf::LOG_PERSISTENCIA_MAX_BYTES) {
            $lineas = @file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lineas) && count($lineas) > 0) {
                $lineas = array_slice($lineas, intdiv(count($lineas), 2));
                @file_put_contents($ruta, implode("\n", $lineas) . "\n");
            }
        }
    }

    @file_put_contents($ruta, $linea, FILE_APPEND | LOCK_EX);

    // Limpieza de archivos viejos: solo en la primera escritura del día.
    if ($primera_vez_hoy) {
        _log_persistencia_limpiar_viejos();
    }
}

/**
 * Devuelve la ruta absoluta al directorio de logs. Lo crea si no existe.
 *
 * @return string|null
 */
function _log_persistencia_directorio(): ?string {
    $raiz = dirname(__DIR__);
    $dir = $raiz . '/' . ltrim(Conf::LOG_PERSISTENCIA_DIR, '/');
    if (substr($dir, -1) !== '/') $dir .= '/';
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
    }
    return $dir;
}

/**
 * Borra los archivos de log más viejos que el límite de retención.
 *
 * @return void
 */
function _log_persistencia_limpiar_viejos(): void {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return;
    $archivos = @glob($dir . '*.log');
    if (!is_array($archivos)) return;
    $limite = time() - Conf::LOG_PERSISTENCIA_RETENER_DIAS * 86400;
    foreach ($archivos as $f) {
        $mt = @filemtime($f);
        if ($mt !== false && $mt < $limite) {
            @unlink($f);
        }
    }
}

/**
 * Lee el contenido del log de una fecha.
 *
 * @param string|null $fecha YYYY-MM-DD. Si null, hoy.
 * @return string|null
 */
function leer_log_persistencia(?string $fecha = null): ?string {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return null;
    if ($fecha === null || $fecha === '') {
        $fecha = date('Y-m-d');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return null;
    $ruta = $dir . $fecha . '.log';
    if (!file_exists($ruta)) return null;
    $c = @file_get_contents($ruta);
    return $c === false ? null : $c;
}

/**
 * Lista los archivos de log disponibles, del más reciente al más viejo.
 *
 * @return array<int, array{fecha: string, tamano: int, tamano_legible: string, mtime: int}>
 */
function listar_logs_persistencia(): array {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return [];
    $archivos = @glob($dir . '*.log');
    if (!is_array($archivos)) return [];
    $res = [];
    foreach ($archivos as $f) {
        $nombre = basename($f);
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})\.log$/', $nombre, $m)) continue;
        $tam = @filesize($f);
        $mt = @filemtime($f);
        $res[] = [
            'fecha' => $m[1],
            'tamano' => (int)($tam === false ? 0 : $tam),
            'tamano_legible' => _log_persistencia_formato_tamano((int)($tam === false ? 0 : $tam)),
            'mtime' => (int)($mt === false ? 0 : $mt),
        ];
    }
    usort($res, function($a, $b) { return $b['fecha'] <=> $a['fecha']; });
    return $res;
}

/**
 * Trunca (borra el contenido) del log de una fecha.
 *
 * @param string|null $fecha YYYY-MM-DD. Si null, hoy.
 * @return bool
 */
function limpiar_log_persistencia(?string $fecha = null): bool {
    $dir = _log_persistencia_directorio();
    if ($dir === null) return false;
    if ($fecha === null || $fecha === '') {
        $fecha = date('Y-m-d');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return false;
    $ruta = $dir . $fecha . '.log';
    if (!file_exists($ruta)) return true;
    return @file_put_contents($ruta, '') !== false;
}

/**
 * Formatea un tamaño en bytes a string legible.
 *
 * @param int $bytes
 * @return string
 */
function _log_persistencia_formato_tamano(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, '.', '') . ' KB';
    return number_format($bytes / (1024 * 1024), 2, '.', '') . ' MB';
}