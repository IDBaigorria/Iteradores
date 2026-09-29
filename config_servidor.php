<?php
namespace Iteradores\Configuracion;

/**
 * Configuración del servidor y credenciales.
 *
 * Este archivo vive en la raíz del proyecto y NO se toca al desplegar.
 * En el servidor, dejälo con las credenciales reales y subí el resto del
 * código encima sin sobreescribirlo.
 *
 * La clase `Conf` (en Configuracion/Configuracion.php) hereda de
 * `ConfServidor`, así que las constantes de acá se usan con el mismo
 * `Conf::NOMBRE_CONSTANTE` en todo el proyecto.
 *
 * @package   Iteradores
 * @since     1.5piloto.72
 * @version   1.5piloto.72
 */
class ConfServidor {

    // Sobre si se ejecuta en localhost o en hosting de internet
    public const LOCAL = true;  // Cambiar a false en producción

    // --- Constantes específicas para entorno local ---
    public const HOST_SQL_LOCAL = "localhost";
    public const USUARIO_SQL_LOCAL = "root";
    public const CONTRASENA_SQL_LOCAL = "";
    public const NOMBRE_BD_SQL_LOCAL = "HyS";

    // --- Constantes específicas para entorno remoto ---
    public const HOST_SQL_REMOTO = "sql200.infinityfree.com";
    public const USUARIO_SQL_REMOTO = "if0_42773340";
    public const CONTRASENA_SQL_REMOTO = "aBjxN1w0SF";
    public const NOMBRE_BD_SQL_REMOTO = "if0_42773340_HyS";

    // --- Constantes específicas para entorno remoto (alternativa) ---
    /*
    public const HOST_SQL_REMOTO = "sql303.infinityfree.com";
    public const USUARIO_SQL_REMOTO = "if0_42770299";
    public const CONTRASENA_SQL_REMOTO = "0EnWvlaHCp";
    public const NOMBRE_BD_SQL_REMOTO = "if0_42770299_HyS";
    */

    // --- Constantes finales (se eligen según LOCAL) ---
    public const HOST_SQL = self::LOCAL ? self::HOST_SQL_LOCAL : self::HOST_SQL_REMOTO;
    public const USUARIO_SQL = self::LOCAL ? self::USUARIO_SQL_LOCAL : self::USUARIO_SQL_REMOTO;
    public const CONTRASENA_SQL = self::LOCAL ? self::CONTRASENA_SQL_LOCAL : self::CONTRASENA_SQL_REMOTO;
    public const NOMBRE_BD_SQL = self::LOCAL ? self::NOMBRE_BD_SQL_LOCAL : self::NOMBRE_BD_SQL_REMOTO;

    // --- Constantes para la persistencia de la superestructura ---
    // Se heredan de las constantes principales (ya elegidas según LOCAL)
    public const SUPERESTRUCTURA_HOST_SQL = self::HOST_SQL;
    public const SUPERESTRUCTURA_USUARIO_SQL = self::USUARIO_SQL;
    public const SUPERESTRUCTURA_CONTRASENA_SQL = self::CONTRASENA_SQL;
    public const SUPERESTRUCTURA_NOMBRE_BD_SQL = self::NOMBRE_BD_SQL;

    // --- Credenciales del admin principal ---
    public const CODIGO_ADMIN = 'IDB';
}