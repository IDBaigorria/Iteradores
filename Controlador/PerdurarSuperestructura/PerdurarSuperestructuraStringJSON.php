<?php
namespace Iteradores\Controlador\PerdurarSuperestructura;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
use Iteradores\Configuracion\Conf;
use Iteradores\Controlador\PerdurarSuperestructura\PerdurarSuperestructura;
include_once(".\Nucleo\Objeto.php");
include_once(".\Nodos\Nodo.php");
include_once(".\Configuracion\Configuracion.php");
include_once(".\Controlador\PerdurarSuperestructura\PerdurarSuperestructura.php");

/**
 * Clase PerdurarSuperestructuraJSON
 * 
 * @version 1.0.0 (Última revisión: 01/09/2025)
 * @author ...
 * 
 * @extends Objeto
 * 
 * @description
 * Clase responsable de la persistencia de la superestructura en archivos JSON.
 * Se encarga de crear la estructura de carpetas y archivos necesarios
 * para almacenar los nodos y sus relaciones en formato JSON.
 * 
 * @history
 * - 01/09/2025: Implementación inicial con almacenamiento en JSON
 * 
 * @notes
 * Esta clase utiliza una estructura JSON más natural para representar
 * nodos con sus adyacentes, en contraste con el enfoque relacional de SQL.
 */
class PerdurarSuperestructuraStringJSON extends Objeto implements PerdurarSuperestructura
{

    /**
     * @var string Token de seguridad recibido de la clase Nodo.
     */
    protected static string $token = '';

    /**
     * Recibe el token de seguridad desde la clase Controlador
     *
     * @param string $token Token de seguridad proporcionado por Nodo.
     * @return void
     */
    public static function recibir_token(string $token): void {
        static::$token = $token;
    }

    /**
     * Crea la carpeta de almacenamiento si no existe.
     * 
     * @usecase Asegurar que exista el directorio para guardar archivos JSON.
     * 
     * @preconditions
     * - La constante SUPERESTRUCTURA_CARPETA_GUARDAR_JSON debe estar definida en Conf.
     * 
     * @return bool `true` si la carpeta existe o fue creada, `false` en caso de error.
     * 
     * @postconditions
     * - La carpeta de almacenamiento queda disponible para guardar archivos.
     */
    static private function crear_carpeta_json(): bool
    {
        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_JSON;
        
        if (!is_dir($carpeta)) {
            if (!mkdir($carpeta, 0755, true)) {
                self::_error("No se pudo crear la carpeta de almacenamiento JSON: " . $carpeta);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obtiene la ruta completa del archivo JSON para una superestructura.
     * 
     * @usecase Generar la ruta del archivo basado en el nombre de la superestructura.
     * 
     * @param string $nombre Identificador de la superestructura.
     * 
     * @return string Ruta completa del archivo JSON.
     */
    static private function obtener_ruta_archivo(string $nombre): string
    {
        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_JSON;
        // Sanitizar el nombre para evitar problemas con el sistema de archivos
        $nombre_archivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre) . '.json';
        return $carpeta . DIRECTORY_SEPARATOR . $nombre_archivo;
    }

    /**
     * Construye la estructura de datos para guardar en JSON.
     * 
     * @usecase Recopilar todos los nodos y sus relaciones en una estructura serializable.
     * 
     * @return array Estructura de datos lista para convertir a JSON.
     * 
     * @notes
     * La estructura incluye todos los nodos con sus datos y adyacentes,
     * manteniendo las referencias entre ellos.
     */
    static private function construir_estructura_json(): array
    {
        $estructura = [
            'metadata' => [
//                'version' => '1.0',
                'fecha_creacion' => date('c'),
                'total_nodos' => 0
            ],
            'nodos' => []
        ];

        // Obtener todos los nodos con sus datos
        $datos_nodos = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            return $nodo->dato();
        }, null) ?: [];

        // Obtener todos los adyacentes
        $datos_adyacentes = Nodo::por_cada_nodo_ejecutar(static::$token, function ($nodo) {
            return $nodo->por_cada_adyacente_ejecutar(function ($adyacente) {
                return $adyacente->id();
            });
        }, null) ?: [];

        // Construir la estructura de nodos
        foreach ($datos_nodos as $id => $dato) {
            $nodo = [
                'id' => $id,
                'dato' => $dato,
                'adyacentes' => []
            ];

            // Agregar adyacentes si existen
            if (isset($datos_adyacentes[$id]) && is_array($datos_adyacentes[$id])) {
                foreach ($datos_adyacentes[$id] as $enlace => $idadyacente) {
                    $nodo['adyacentes'][$enlace] = $idadyacente;
                }
            }

            $estructura['nodos'][] = $nodo;
        }

        $estructura['metadata']['total_nodos'] = count($estructura['nodos']);

        return $estructura;
    }

    /**
     * Guarda la superestructura en un archivo JSON.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Persistir toda la superestructura en formato JSON.
     * 
     * @preconditions Debe existir al menos un nodo en la superestructura.
     * 
     * @param string $nombre Identificador único para guardar la superestructura.
     * 
     * @return bool `true` si la operación fue exitosa, `false` en caso contrario.
     * 
     * @notes 
     * - Crea la carpeta de almacenamiento si no existe
     * - Genera un archivo JSON con toda la estructura de nodos y enlaces
     */
    static public function guardar($nombre): bool
    {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_error("error en guardar, no existe ningun nodo en la superestructura");
            return false;
        }

        if (!self::crear_carpeta_json()) {
            return false;
        }

        $ruta_archivo = self::obtener_ruta_archivo($nombre);
        $estructura = self::construir_estructura_json();

        $json = json_encode($estructura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            self::_error("Error al codificar la estructura a JSON");
            return false;
        }

        if (file_put_contents($ruta_archivo, $json) === false) {
            self::_error("No se pudo guardar el archivo JSON: " . $ruta_archivo);
            return false;
        }

        return true;
    }

    /**
     * Elimina una superestructura guardada en JSON.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Remover un archivo JSON persistido por nombre.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a eliminar.
     * 
     * @return bool|null `true` si fue eliminada, `false` si no existía, `null` en caso de error.
     * 
     * @postconditions El archivo JSON con ese nombre queda eliminado.
     */
    static public function eliminar($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("eliminar: el identificador pasado como parametro no es un string");
            return null;
        }

        $ruta_archivo = self::obtener_ruta_archivo($nombre);

        if (!file_exists($ruta_archivo)) {
            self::_error("eliminar: no existe superestructura con ese nombre");
            return false;
        }

        if (!unlink($ruta_archivo)) {
            self::_error("Error al eliminar el archivo JSON: " . $ruta_archivo);
            return null;
        }

        return true;
    }

    /**
     * Carga una superestructura desde un archivo JSON.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Recuperar una superestructura persistida por nombre desde JSON.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a cargar.
     * 
     * @return bool|null `true` si la carga fue exitosa, `false` si no existe, `null` en caso de error.
     * 
     * @postconditions La superestructura queda cargada en memoria.
     * 
     * @notes Reconstruye los nodos y sus relaciones desde el archivo JSON.
     */
    static public function cargar($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("cargar: el identificador pasado como parametro no es un string");
            return false;
        }

        $ruta_archivo = self::obtener_ruta_archivo($nombre);

        if (!file_exists($ruta_archivo)) {
            self::_alerta("alerta al cargar, no existe superestructura con el identificador pasado como parametro");
            return false;
        }

        $contenido = file_get_contents($ruta_archivo);
        if ($contenido === false) {
            self::_error("Error al leer el archivo JSON: " . $ruta_archivo);
            return null;
        }

        $estructura = json_decode($contenido, true);
        if ($estructura === null) {
            self::_error("Error al decodificar el archivo JSON: " . $ruta_archivo);
            return null;
        }

        // Limpiar la superestructura actual antes de cargar
        Nodo::vaciar_superestructura(static::$token);

        $equivalencias = [];

        // Primero crear todos los nodos
        foreach ($estructura['nodos'] as $nodo_data) {
            $id = $nodo_data['id'];
            $dato = $nodo_data['dato'];

            if (self::es_id_especial($id)) {
                if (!$naux = Nodo::nodo_por_id($id)) {
                    Nodo::crear_con_dato_e_id($dato, $id);
                } else {
                    $naux->_dato($dato);
                }
            } else {
                $idnuevo = Nodo::crear_con_dato($dato)->id();
                $equivalencias[$id] = $idnuevo;
            }
        }

        // Luego establecer las relaciones de adyacencia
        foreach ($estructura['nodos'] as $nodo_data) {
            $id_original = $nodo_data['id'];
            $adyacentes = $nodo_data['adyacentes'] ?? [];

            // Determinar el ID real del nodo (puede haber cambiado por equivalencias)
            $id_nodo = self::es_id_especial($id_original) ? $id_original : ($equivalencias[$id_original] ?? $id_original);
            $nodo = Nodo::nodo_por_id($id_nodo);

            if ($nodo && !empty($adyacentes)) {
                foreach ($adyacentes as $enlace => $id_adyacente_original) {
                    // Determinar el ID real del adyacente
                    $id_adyacente = self::es_id_especial($id_adyacente_original) ? 
                                   $id_adyacente_original : 
                                   ($equivalencias[$id_adyacente_original] ?? $id_adyacente_original);
                    
                    $nodo_adyacente = Nodo::nodo_por_id($id_adyacente);
                    if ($nodo_adyacente) {
                        $nodo->_adyacente_en($nodo_adyacente, $enlace);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Verifica la existencia de una superestructura en archivo JSON.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Consultar si existe una superestructura persistida por nombre.
     * 
     * @param string $nombre Identificador de la superestructura a verificar.
     * 
     * @return bool|null `true` si existe, `false` si no existe, `null` en caso de error.
     */
    static public function existe($nombre): bool|null
    {
        if (!is_string($nombre)) {
            self::_error("existe: el identificador pasado como parametro no es un string");
            return null;
        }

        $ruta_archivo = self::obtener_ruta_archivo($nombre);
        return file_exists($ruta_archivo);
    }

    /**
     * Lista todas las superestructuras guardadas en formato JSON.
     * 
     * @usecase Obtener un listado de todas las superestructuras disponibles.
     * 
     * @return array|null Array con los nombres de las superestructuras, o null en caso de error.
     */
    static public function listar(): ?array
    {
        if (!self::crear_carpeta_json()) {
            return null;
        }

        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_JSON;
        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . '*.json');
        
        $superestructuras = [];
        foreach ($archivos as $archivo) {
            $nombre = pathinfo($archivo, PATHINFO_FILENAME);
            $superestructuras[] = $nombre;
        }

        return $superestructuras;
    }

}