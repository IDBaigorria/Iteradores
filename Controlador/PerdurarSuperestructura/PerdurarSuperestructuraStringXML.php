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
 * Clase PerdurarSuperestructuraXML
 * 
 * @version 1.0.0 (Última revisión: 01/09/2025)
 * @author ...
 * 
 * @extends Objeto
 * 
 * @description
 * Clase responsable de la persistencia de la superestructura en archivos XML.
 * Se encarga de crear la estructura de carpetas y archivos necesarios
 * para almacenar los nodos y sus relaciones en formato XML.
 * 
 * @history
 * - 01/09/2025: Implementación inicial con almacenamiento en XML
 * 
 * @notes
 * Esta clase utiliza una estructura XML para representar
 * nodos con sus adyacentes, alternativa al formato JSON.
 */
class PerdurarSuperestructuraStringXML extends Objeto implements PerdurarSuperestructura
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
     * @usecase Asegurar que exista el directorio para guardar archivos XML.
     * 
     * @preconditions
     * - La constante SUPERESTRUCTURA_CARPETA_GUARDAR_XML debe estar definida en Conf.
     * 
     * @return bool `true` si la carpeta existe o fue creada, `false` en caso de error.
     * 
     * @postconditions
     * - La carpeta de almacenamiento queda disponible para guardar archivos.
     */
    static private function crear_carpeta_xml(): bool
    {
        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_XML;
        echo "miiiii";
        if (!is_dir($carpeta)) {
            echo "SIIIIIIIIIII";
            if (!mkdir($carpeta, 0755, true)) {
                self::_error("No se pudo crear la carpeta de almacenamiento XML: " . $carpeta);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Obtiene la ruta completa del archivo XML para una superestructura.
     * 
     * @usecase Generar la ruta del archivo basado en el nombre de la superestructura.
     * 
     * @param string $nombre Identificador de la superestructura.
     * 
     * @return string Ruta completa del archivo XML.
     */
    static private function obtener_ruta_archivo(string $nombre): string
    {
        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_XML;
        // Sanitizar el nombre para evitar problemas con el sistema de archivos
        $nombre_archivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombre) . '.xml';
        return $carpeta . DIRECTORY_SEPARATOR . $nombre_archivo;
    }

    /**
     * Construye la estructura de datos para guardar en XML.
     * 
     * @usecase Recopilar todos los nodos y sus relaciones en una estructura XML serializable.
     * 
     * @return string Estructura de datos en formato XML lista para guardar.
     * 
     * @notes
     * La estructura incluye todos los nodos con sus datos y adyacentes,
     * manteniendo las referencias entre ellos en formato XML.
     */
    static private function construir_estructura_xml(): string
    {
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

        // Crear documento XML
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Elemento raíz
        $superestructura = $dom->createElement('superestructura');
        $dom->appendChild($superestructura);

        // Metadata
        $metadata = $dom->createElement('metadata');
        $superestructura->appendChild($metadata);

        $fecha_creacion = $dom->createElement('fecha_creacion', date('c'));
        $metadata->appendChild($fecha_creacion);

        $total_nodos = $dom->createElement('total_nodos', count($datos_nodos));
        $metadata->appendChild($total_nodos);

        // Nodos
        $nodos = $dom->createElement('nodos');
        $superestructura->appendChild($nodos);

        // Construir la estructura de nodos
        foreach ($datos_nodos as $id => $dato) {
            $nodo = $dom->createElement('nodo');
            $nodo->setAttribute('id', $id);
            $nodos->appendChild($nodo);

            $dato_element = $dom->createElement('dato', self::escapar_xml($dato ?? ''));
            $nodo->appendChild($dato_element);

            $adyacentes_element = $dom->createElement('adyacentes');
            $nodo->appendChild($adyacentes_element);

            // Agregar adyacentes si existen
            if (isset($datos_adyacentes[$id]) && is_array($datos_adyacentes[$id])) {
                foreach ($datos_adyacentes[$id] as $enlace => $idadyacente) {
                    $adyacente = $dom->createElement('adyacente', $idadyacente);
                    $adyacente->setAttribute('enlace', $enlace);
                    $adyacentes_element->appendChild($adyacente);
                }
            }
        }

        return $dom->saveXML();
    }

    /**
     * Escapa caracteres especiales para XML
     * 
     * @param string $texto Texto a escapar
     * @return string Texto escapado
     */
    static private function escapar_xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Guarda la superestructura en un archivo XML.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Persistir toda la superestructura en formato XML.
     * 
     * @preconditions Debe existir al menos un nodo en la superestructura.
     * 
     * @param string $nombre Identificador único para guardar la superestructura.
     * 
     * @return bool `true` si la operación fue exitosa, `false` en caso contrario.
     * 
     * @notes 
     * - Crea la carpeta de almacenamiento si no existe
     * - Genera un archivo XML con toda la estructura de nodos y enlaces
     */
    static public function guardar($nombre): bool
    {
        if (!Nodo::hay_nodos_en_superestructura()) {
            self::_error("error en guardar, no existe ningun nodo en la superestructura");
            return false;
        }
        echo "acaa";
        if (!self::crear_carpeta_xml()) {
            return false;
        }

        $ruta_archivo = self::obtener_ruta_archivo($nombre);
        $xml = self::construir_estructura_xml();

        if (file_put_contents($ruta_archivo, $xml) === false) {
            self::_error("No se pudo guardar el archivo XML: " . $ruta_archivo);
            return false;
        }

        return true;
    }

    /**
     * Elimina una superestructura guardada en XML.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Remover un archivo XML persistido por nombre.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a eliminar.
     * 
     * @return bool|null `true` si fue eliminada, `false` si no existía, `null` en caso de error.
     * 
     * @postconditions El archivo XML con ese nombre queda eliminado.
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
            self::_error("Error al eliminar el archivo XML: " . $ruta_archivo);
            return null;
        }

        return true;
    }

    /**
     * Carga una superestructura desde un archivo XML.
     * 
     * @interface PerdurarSuperestructura
     * 
     * @usecase Recuperar una superestructura persistida por nombre desde XML.
     * 
     * @preconditions Debe existir una superestructura guardada con el nombre especificado.
     * 
     * @param string $nombre Identificador de la superestructura a cargar.
     * 
     * @return bool|null `true` si la carga fue exitosa, `false` si no existe, `null` en caso de error.
     * 
     * @postconditions La superestructura queda cargada en memoria.
     * 
     * @notes Reconstruye los nodos y sus relaciones desde el archivo XML.
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
            self::_error("Error al leer el archivo XML: " . $ruta_archivo);
            return null;
        }

        // Parsear XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($contenido);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = $error->message;
            }
            self::_error("Error al decodificar el archivo XML: " . implode('; ', $error_messages));
            return null;
        }

        // Limpiar la superestructura actual antes de cargar
        Nodo::vaciar_superestructura(static::$token);

        $equivalencias = [];

        // Primero crear todos los nodos
        foreach ($xml->nodos->nodo as $nodo_xml) {
            $id = (string)$nodo_xml['id'];
            $dato = (string)$nodo_xml->dato;

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
        foreach ($xml->nodos->nodo as $nodo_xml) {
            $id_original = (string)$nodo_xml['id'];
            $adyacentes = [];

            // Recoger todos los adyacentes
            foreach ($nodo_xml->adyacentes->adyacente as $adyacente_xml) {
                $enlace = (string)$adyacente_xml['enlace'];
                $idadyacente = (string)$adyacente_xml;
                $adyacentes[$enlace] = $idadyacente;
            }

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
     * Verifica la existencia de una superestructura en archivo XML.
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
     * Lista todas las superestructuras guardadas en formato XML.
     * 
     * @usecase Obtener un listado de todas las superestructuras disponibles.
     * 
     * @return array|null Array con los nombres de las superestructuras, o null en caso de error.
     */
    static public function listar(): ?array
    {
        if (!self::crear_carpeta_xml()) {
            return null;
        }

        $carpeta = Conf::SUPERESTRUCTURA_CARPETA_GUARDAR_XML;
        $archivos = glob($carpeta . DIRECTORY_SEPARATOR . '*.xml');
        
        $superestructuras = [];
        foreach ($archivos as $archivo) {
            $nombre = pathinfo($archivo, PATHINFO_FILENAME);
            $superestructuras[] = $nombre;
        }

        return $superestructuras;
    }

    /**
     * Carga una superestructura desde un string XML.
     * 
     * @usecase Cargar una superestructura desde datos XML en memoria.
     * 
     * @param string $xml_string String XML con la estructura de la superestructura.
     * 
     * @return bool `true` si la carga fue exitosa, `false` en caso contrario.
     * 
     * @notes
     * Útil para cargar superestructuras desde fuentes externas
     * o datos previamente cargados en memoria.
     */
    static public function cargar_desde_xml(string $xml_string): bool
    {
        // Parsear XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_string);
        if ($xml === false) {
            $errors = libxml_get_errors();
            $error_messages = [];
            foreach ($errors as $error) {
                $error_messages[] = $error->message;
            }
            self::_error("Error al decodificar el XML: " . implode('; ', $error_messages));
            return false;
        }

        // Limpiar la superestructura actual antes de cargar
        Nodo::vaciar_superestructura(static::$token);

        $equivalencias = [];

        // Primero crear todos los nodos
        foreach ($xml->nodos->nodo as $nodo_xml) {
            $id = (string)$nodo_xml['id'];
            $dato = (string)$nodo_xml->dato;

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
        foreach ($xml->nodos->nodo as $nodo_xml) {
            $id_original = (string)$nodo_xml['id'];
            $adyacentes = [];

            // Recoger todos los adyacentes
            foreach ($nodo_xml->adyacentes->adyacente as $adyacente_xml) {
                $enlace = (string)$adyacente_xml['enlace'];
                $idadyacente = (string)$adyacente_xml;
                $adyacentes[$enlace] = $idadyacente;
            }

            // Determinar el ID real del nodo
            $id_nodo = self::es_id_especial($id_original) ? $id_original : ($equivalencias[$id_original] ?? $id_original);
            $nodo = Nodo::nodo_por_id($id_nodo);

            if ($nodo && !empty($adyacentes)) {
                foreach ($adyacentes as $enlace => $id_adyacente_original) {
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

}