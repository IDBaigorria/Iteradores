<?php
session_start();
header("Cache-control: no-cache, must-revalidate");

use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Conf;
use Iteradores\Configuracion\Entorno;
use Iteradores\Nodos\Nodo;

/**
 * Punto de entrada principal del framework.
 *
 * Orquesta la carga de todos los módulos del sistema en un orden
 * específico que garantiza la ausencia de dependencias circulares
 * y permite que los componentes se autoencolen correctamente en
 * {@link \Iteradores\Controlador\RegistroGlobal} antes de que el
 * {@link \Iteradores\Controlador\Controlador} sea inicializado.
 *
 * @author Ignacio David Baigorria
 * @package   Iteradores
 * @since     1.0.0
 * @version   1.5piloto.76u
 */

// --- Utilidades base ----------------------------------
include_once("miscelaneas/benchmark.php");
include_once("miscelaneas/generarUUID.php");

// --- Configuración ------------------------------------
include_once("Configuracion/Configuracion.php");

// --- Núcleo -------------------------------------------
include_once("Nucleo/Objeto.php");

// --- Entorno (debe ir después de Objeto.php) ----------
include_once("Configuracion/Entorno.php");

// --- Nodos --------------------------------------------
include_once("Nodos/Nodo.php");
include_once("Nodos/NodoElectrico.php");
include_once("Nodos/Matriz2x2.php");
include_once("Nodos/NodoNumerico.php");
include_once("Nodos/NodoPrimo.php");
include_once("Nodos/NodoParalelo.php");

// --- V 1.4.8 – Comunicación: Señal y Antenas ---------
include_once("Iteradores/Senal.php");
include_once("Iteradores/AntenaComun.php");
include_once("Iteradores/AntenaDeMarcado.php");
include_once("Controlador/AntenaTraduccion.php");

// --- Iteradores ---------------------------------------
include_once("Controlador/ProcesadorDeDominio.php");
include_once("Controlador/Talamo.php");

// --- Tiempo -------------------------------------------
include_once("Tiempo/RelojAstronomico.php");

// --- Comandos y Comunicadores (autoencolación) ---------
include_once("Controlador/RegistroGlobal.php");
include_once("Comandos/index.php");
include_once("Comunicadores/index.php");

// --- Controlador (inicialización) ----------------------
include_once("Controlador/Controlador.php");

// Incluir módulos de la aplicación.
// Primero `ConfiguracionApli.php`, que define las constantes
// propias del piloto (hereda del `Conf` del framework).
require_once __DIR__ . '/Aplicacion/ConfiguracionApli.php';
// `FuncionesAuxiliares.php` define `guardar_ambos`.
require_once __DIR__ . '/Aplicacion/FuncionesAuxiliares.php';
require_once __DIR__ . '/Aplicacion/GrafoCredenciales.php';
require_once __DIR__ . '/Aplicacion/Usuarios/Usuario.php';
require_once __DIR__ . '/Aplicacion/Sesiones/Sesion.php';
require_once __DIR__ . '/Aplicacion/Admin/Admin.php';
require_once __DIR__ . '/Aplicacion/Autenticacion/Autenticacion.php';
require_once __DIR__ . '/Aplicacion/Empresas/Empresa.php';
require_once __DIR__ . '/Aplicacion/Vehiculos/Vehiculo.php';
require_once __DIR__ . '/Aplicacion/Viajes/Viaje.php';
require_once __DIR__ . '/Aplicacion/Ventas/Venta.php';
require_once __DIR__ . '/Aplicacion/Pasajeros/Pasajero.php';
require_once __DIR__ . '/Aplicacion/Rendiciones/Rendicion.php';
require_once __DIR__ . '/Aplicacion/Liquidaciones/Liquidacion.php';
require_once __DIR__ . '/Aplicacion/Enrutador.php';
require_once __DIR__ . '/Aplicacion/Migraciones/Registro.php';
require_once __DIR__ . '/Aplicacion/Migraciones/Comandos.php';

// Registrar los comandos app:migracion_* en el Controlador.
// El Controlador ya está inicializado (se autoinicializa al
// final de Controlador.php, que se incluyó antes).
registrar_comandos_migraciones();

// Modo de ejecución. En local se considera modo pruebas
// (habilita los botones de limpieza del admin). En
// producción, modo producción (los botones quedan
// ocultos porque el HTML no incluye la bandera JS).
Entorno::establecer_modo(Conf::LOCAL ? Entorno::MODO_PRUEBAS : Entorno::MODO_PRODUCCION);
// Alinear el prefijo de sesión del framework con el del piloto
// (el framework no conoce el nombre de la app).
Entorno::establecer_prefijo_sesion(ConfiguracionApli::PREFIJO_SESSION);

// Inicialización de la persistencia.
// Va después de los require_once para que `guardar_ambos` esté
// definida (vive en FuncionesAuxiliares.php).
Controlador::establecer_metodo('SQL');
$nombre_app = ConfiguracionApli::NOMBRE_APP;
if (Controlador::existe($nombre_app)) {
    if (!Controlador::cargar($nombre_app)) {
        // Existe en SQL pero no se pudo cargar. No caemos al else:
        // guardar vacío pisaría el grafo. Morimos con mensaje claro.
        Controlador::_error("index.php: no se pudo cargar el grafo \"$nombre_app\".");
        http_response_code(500);
        die('Error fatal: no se pudo cargar el grafo principal. Revise los logs.');
    }
} else {
    // Primera ejecución: no existe el grafo. Se crea vacío.
    guardar_ambos($nombre_app);
}

// ==== Bloque temporal para prueba del depósito de IDs (v1.5i.7a) ====
if (isset($_GET['probar_deposito'])) {
    require_once __DIR__ . '/Pruebas/prueba_deposito.php';
    exit;
}

// ==== Bloque temporal para prueba de persistencia del iterador (v73s) ====
if (isset($_GET['probar_iterador'])) {
    require_once __DIR__ . '/Pruebas/pruebas_iterador_persistencia.php';
    exit;
}

// ==== Bloque temporal para verificar el fix if($elemento) en Iterador (v73s) ====
if (isset($_GET['probar_elemento_cero'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/Iteradores/Iterador.php';
    $iter = \Iteradores\Iteradores\Iterador::crear('test_cero', 0);
    if (!$iter) {
        echo "FALLO: no se pudo crear el iterador\n";
        exit;
    }
    $dato = $iter->dato();
    echo "dato() = " . var_export($dato, true) . "\n";
    if ($dato === 0) {
        echo "SIN BUG: el elemento 0 se asignó correctamente.\n";
    } else {
        echo "BUG PRESENTE: el elemento 0 se descartó.\n";
    }
    $iter->destruir();
    exit;
}

// ==== Bloque temporal para pruebas de árbol ====
if (isset($_GET['probar_arbol'])) {
    require_once __DIR__ . '/miscelaneas/Arbol.php';
    require_once __DIR__ . '/miscelaneas/pruebas_arbol.php';
    exit;
}

// ==== Bloque temporal para prueba de contextos (v1.5i.7k) ====
if (isset($_GET['probar_contextos'])) {
    require_once __DIR__ . '/Pruebas/prueba_contextos.php';
    exit;
}

// Crear el nodo especial `aplicacion` con su contenedor
// `migraciones`, si no existen. Se usa para marcar las
// migraciones aplicadas.
if (!Nodo::nodo_por_id('aplicacion')) {
    $nodo_app_meta = Nodo::crear_con_id('aplicacion');
    if ($nodo_app_meta) {
        $contenedor_mig = Nodo::crear_con_dato('');
        $nodo_app_meta->_adyacente_en($contenedor_mig, 'migraciones');
        guardar_ambos($nombre_app);
    }
} else {
    // Existe el nodo aplicacion pero puede faltar el contenedor.
    $nodo_app_meta = Nodo::nodo_por_id('aplicacion');
    if ($nodo_app_meta && !$nodo_app_meta->adyacente('migraciones')) {
        $contenedor_mig = Nodo::crear_con_dato('');
        $nodo_app_meta->_adyacente_en($contenedor_mig, 'migraciones');
        guardar_ambos($nombre_app);
    }
}

// ==== Migración de niveles de usuario (v76m) ====
// Crea los contenedores `publico` y `privado` en cada
// usuario. NO elimina los enlaces viejos: los contenedores
// son alias a los mismos nodos físicos. Idempotente.
// Parámetro opcional `usuario` (default `todos`).
if (isset($_GET['migrar_niveles_usuario'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/migrar_niveles_usuario.php';
    $usuario_obj = $_GET['usuario'] ?? 'todos';
    $res = migrar_niveles_usuario($nombre_app, $usuario_obj);
    echo "Migración de niveles de usuario\n";
    echo "================================\n\n";
    echo "Usuario objetivo: $usuario_obj\n\n";
    echo "Migrados: " . $res['migrados'] . "\n";
    echo "Saltados: " . $res['saltados'] . "\n";
    if (!empty($res['errores'])) {
        echo "Errores:\n";
        foreach ($res['errores'] as $e) echo "  - $e\n";
    }
    echo "\nListo.\n";
    exit;
}

// ==== Migración de compartidos por terminal (v76q) ====
// Crea los contenedores `compartido_con_us_termX` en
// cada dueño, con referencias filtradas a los viajes,
// empresas, ventas y cancelaciones del terminal. NO
// repunta los accesos viejos: los compartidos coexisten
// con la estructura actual. Idempotente.
if (isset($_GET['migrar_compartidos_terminal'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/migrar_compartidos_terminal.php';
    $res = migrar_compartidos_terminal();
    echo "Migración de compartidos por terminal\n";
    echo "=========================================\n\n";
    echo "Creados:   " . $res['creados'] . "\n";
    echo "Salteados: " . $res['salteados'] . "\n";
    if (!empty($res['errores'])) {
        echo "Errores:\n";
        foreach ($res['errores'] as $e) echo "  - $e\n";
    }
    echo "\nListo.\n";
    exit;
}

// ==== Migración de usuarios a IDs especiales (v76k) ====
// Convierte cada nodo usuario a ID especial us_<nombre>.
// Idempotente: si ya están migrados, no hace nada.
if (isset($_GET['migrar_usuarios_especiales'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/migrar_usuarios_especiales.php';
    $res = migrar_usuarios_a_especiales($nombre_app);
    echo "Migración de usuarios a IDs especiales\n";
    echo "==========================================\n\n";
    foreach ($res as $grafo => $detalle) {
        echo "-- $grafo --\n";
        if (is_array($detalle)) {
            foreach ($detalle as $k => $v) {
                if (is_array($v)) {
                    echo "  $k:\n";
                    foreach ($v as $linea) echo "    $linea\n";
                } else {
                    echo "  $k => " . var_export($v, true) . "\n";
                }
            }
        } else {
            echo "  " . var_export($detalle, true) . "\n";
        }
        echo "\n";
    }
    echo "Listo.\n";
    exit;
}
// (bloque ?migrar_micros eliminado en v73r)
// (bloque ?migrar_micros_patente eliminado en v73r)
// (bloque ?migrar_terminales_autorizadas eliminado en v73r)
// (bloque ?migrar_cupones eliminado en v73r)

// (bloque ?migrar_nombres_pasajeros eliminado en v73r)

// (bloque ?migrar_fecha_ultima_modificacion_pasajeros eliminado en v73r)

// (bloque ?migrar_declaraciones_juradas_v2 eliminado en v73r)

// (bloque ?migrar_declaraciones_juradas_v3 eliminado en v73r)

// (bloque ?migrar_fichas_medicas eliminado en v73r)

// (bloque ?migrar_hashear_credenciales eliminado en v73r)

// (bloque ?migrar_separar_grafos eliminado en v73r)

// Crear usuario administrador si no existe (en ambos grafos).
$raiz_usuarios = Nodo::nodo_por_id('usuarios');
$nodo_admin_existente = $raiz_usuarios ? $raiz_usuarios->adyacente(ConfiguracionApli::NOMBRE_ADMIN) : null;
if (!$nodo_admin_existente) {
    if (!$raiz_usuarios) {
        Nodo::crear_con_id('usuarios');
        $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    }
    $id_especial_admin = 'us_' . ConfiguracionApli::NOMBRE_ADMIN;
    $nodo_admin = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin);
    if ($nodo_admin) {
        $nodo_admin->_adyacente_en(Nodo::crear_con_dato(ConfiguracionApli::NOMBRE_ADMIN), 'nombre_real');
        $nodo_admin->_adyacente_en(Nodo::crear_con_dato('admin'), 'nivel');
        $raiz_usuarios->_adyacente_en($nodo_admin, ConfiguracionApli::NOMBRE_ADMIN);
    }
    guardar_ambos($nombre_app);
}

// Crear admin en el grafo de credenciales si no existe.
en_grafo_credenciales(function() {
    $raiz_cred = Nodo::nodo_por_id('usuarios');
    if (!$raiz_cred) {
        Nodo::crear_con_id('usuarios');
        $raiz_cred = Nodo::nodo_por_id('usuarios');
    }
    if (!$raiz_cred->adyacente(ConfiguracionApli::NOMBRE_ADMIN)) {
        $id_especial_admin_cred = 'us_' . ConfiguracionApli::NOMBRE_ADMIN;
        $nodo_admin_cred = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin_cred);
        if ($nodo_admin_cred) {
            $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), 'codigo_hash');
            $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);
        }
    }
});

// Manejo de impresión
if (isset($_GET['imprimir']) && $_GET['imprimir'] === '1') {
    require_once __DIR__ . '/Aplicacion/Impresion/Impresion.php';
    $tipo = $_GET['tipo'] ?? '';

    // Caso especial: informe de ventas de la pestaña Vendidos.
    // Se pasa el tipo de solicitante, el nombre, los filtros aplicados
    // y los datos del usuario que lo pide.
    if ($tipo === 'informe_ventas') {
        imprimir_informe_ventas($_GET);
        exit;
    }

    // Caso especial: pasaje de asiento reservado para el equipo (sin venta).
    // Se pasa dueño, viaje, micro, fila y columna por GET.
    if ($tipo === 'pasaje_reserva') {
        imprimir_pasaje_reserva(
            $_GET['dueno'] ?? '',
            $_GET['viaje'] ?? '',
            $_GET['micro'] ?? '',
            $_GET['fila'] ?? '',
            $_GET['columna'] ?? ''
        );
        exit;
    }
    // Caso especial: pasajes actualizados de un pasajero en viajes activos.
    // Se pasa dueño y DNI por GET.
    if ($tipo === 'pasajes_actualizados') {
        imprimir_pasajes_actualizados(
            $_GET['dueno'] ?? '',
            $_GET['dni'] ?? ''
        );
        exit;
    }

    // Caso especial: informe imprimible de una rendición.
    // Se pasa el id_rendicion por GET.
    if ($tipo === 'informe_rendicion') {
        imprimir_informe_rendicion($_GET['id_rendicion'] ?? '');
        exit;
    }

    // Caso especial: informe imprimible de una liquidación.
    // Si el dueño es quien imprime, el frontend manda
    // ocultar_datos_generales=1 para no mostrarle su propio nombre.
    if ($tipo === 'informe_liquidacion') {
        $ocultar_datos = ($_GET['ocultar_datos_generales'] ?? '0') === '1';
        imprimir_informe_liquidacion($_GET['id_liquidacion'] ?? '', $ocultar_datos);
        exit;
    }

    // Caso especial: informe imprimible de una cancelación de compra.
    if ($tipo === 'informe_cancelacion') {
        imprimir_informe_cancelacion($_GET['id_cancelacion'] ?? '');
        exit;
    }

    // Caso especial: croquis del micro con los pasajeros.
    if ($tipo === 'croquis_micro') {
        imprimir_croquis_micro(
            $_GET['dueno'] ?? '',
            $_GET['viaje'] ?? '',
            $_GET['micro'] ?? ''
        );
        exit;
    }

    // Caso especial: planilla de pasajeros del micro.
    // Si viene &vacia=1, se imprimen los encabezados y los
    // números de asiento, pero sin los datos del pasajero.
    if ($tipo === 'planilla_pasajeros_micro') {
        $vacia = (($_GET['vacia'] ?? '0') === '1');
        imprimir_planilla_pasajeros_micro(
            $_GET['dueno'] ?? '',
            $_GET['viaje'] ?? '',
            $_GET['micro'] ?? '',
            $vacia
        );
        exit;
    }

    // Caso especial: declaración jurada del viaje (mayor o menor).
    if ($tipo === 'declaracion_jurada') {
        imprimir_declaracion_jurada(
            $_GET['dueno'] ?? '',
            $_GET['viaje'] ?? '',
            $_GET['tipo_dj'] ?? 'mayor'
        );
        exit;
    }
    $dni_filtro = $_GET['dni'] ?? '';
    $numero_cupon = $_GET['numero_cupon'] ?? '';
    generar_impresion($tipo, $_GET['id_venta'] ?? '', $dni_filtro, $numero_cupon);
    exit;
}

// Enrutar según método
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    // enrutar_peticion_post termina en responder_json(), que hace exit.
    // Los guardados internos de cada operación ya se encargan de persistir.
    enrutar_peticion_post($_POST['accion'], $_POST);
    exit;
}

// Si es GET, mostrar la interfaz.
// Se inyecta el modo de pruebas en el HTML (placeholder
// MODO_PRUEBAS_PLACEHOLDER en aplicacion_GET.html). Los
// botones de limpieza del admin consultan esa bandera
// vía `window.entorno_es_pruebas`.
$html = file_get_contents(__DIR__ . '/aplicacion_GET.html');
$es_pruebas_js = Entorno::es_pruebas() ? 'true' : 'false';
$html = str_replace(
    '<!-- MODO_PRUEBAS_PLACEHOLDER -->',
    '<script>window.entorno_es_pruebas = ' . $es_pruebas_js . ';</script>',
    $html
);
echo $html;