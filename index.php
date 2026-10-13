<?php
// Zona horaria del servidor. Sin esto, PHP usa UTC por
// defecto y date('Y-m-d') puede quedar un día adelantado
// respecto del navegador del usuario entre las 21 y las
// 24 hora argentina. Eso rompía el cálculo de antigüedad
// de los datos del pasajero en el autocompletado por DNI
// (avisaba "Fecha de actualizacion invalida").
date_default_timezone_set('America/Argentina/Buenos_Aires');

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
 * @version   1.5piloto.78z
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
require_once __DIR__ . '/Aplicacion/Reparacion/Comandos.php';

// Registrar los comandos app:* en el Controlador.
// El Controlador ya está inicializado (se autoinicializa al
// final de Controlador.php, que se incluyó antes).
registrar_comandos_migraciones();
registrar_comandos_reparacion();

// Modo de ejecución. En local se considera modo pruebas
// (habilita los botones de limpieza del admin). En
// producción, modo producción (los botones quedan
// ocultos porque el HTML no incluye la bandera JS).
Entorno::establecer_modo(Conf::LOCAL ? Entorno::MODO_PRUEBAS : Entorno::MODO_PRODUCCION);
// Alinear el prefijo de sesión del framework con el del piloto
// (el framework no conoce el nombre de la app).
Entorno::establecer_prefijo_sesion(ConfiguracionApli::PREFIJO_SESSION);

// ==== Migración a SQL64 (v1.5piloto.78e) ====
// Pasa el grafo del formato SQL (tablas nodo/adyacente) al
// formato SQL64 (tablas nodo_contexto/adyacente_contexto/
// contexto). SQL64 es el que soporta la carga parcial por
// contextos. Hace su propia carga desde SQL, así no depende
// de que el grafo ya esté en memoria. Idempotente: correrlo
// dos veces no rompe nada (la segunda corrida reescribe las
// mismas tablas con el mismo contenido).
//
// Este bloque va ANTES de la carga normal porque la carga
// normal usa SQL64: si SQL64 todavía no tiene datos, la app
// no puede arrancar.
//
// Uso: index.php?migrar_a_sql64=1
if (isset($_GET['migrar_a_sql64'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $grafos_migrar = [
        'app'          => ConfiguracionApli::NOMBRE_APP,
        'credenciales' => ConfiguracionApli::NOMBRE_APP_CREDENCIALES,
    ];
    echo "Migración a SQL64\n";
    echo "=================\n\n";
    foreach ($grafos_migrar as $etiqueta => $nombre_mig) {
        echo "-- $etiqueta ($nombre_mig) --\n";

        Controlador::establecer_metodo('SQL');
        if (!Controlador::existe($nombre_mig)) {
            echo "   no existe en SQL. Se saltea.\n";
            continue;
        }
        if (!Controlador::cargar($nombre_mig)) {
            echo "   ERROR: no se pudo cargar desde SQL. Se saltea.\n";
            continue;
        }
        if (!Nodo::hay_nodos_en_superestructura()) {
            echo "   el grafo en SQL está vacío. Se saltea.\n";
            continue;
        }

        Controlador::establecer_metodo('SQL64');
        if (!Controlador::guardar($nombre_mig)) {
            echo "   ERROR: no se pudo guardar en SQL64.\n";
            echo "   Ver logs de errores del Controlador.\n";
            Controlador::imprimir_errores();
            exit;
        }

        $ctx = Controlador::listar_contextos($nombre_mig);
        $n_ctx = is_array($ctx) ? count($ctx) : 0;
        echo "   migrado. Contextos: $n_ctx\n";
        if (is_array($ctx) && $n_ctx > 0) {
            foreach ($ctx as $c) echo "     - $c\n";
        }
    }
    echo "\nListo.\n";
    exit;
}

// Inicialización de la persistencia.
// Va después de los require_once para que `guardar_ambos` esté
// definida (vive en FuncionesAuxiliares.php).
//
// NOTA (v1.5piloto.78g, Etapa 1c.2):
// El modo de carga (parcial o total) se decide ANTES de cargar.
// Regla: si hay solicitante, se carga parcial con ['us_<sol>'].
// La topología decide el resto: el admin tiene un enlace a
// `usuarios` desde su propio nodo, así que su carga parcial
// termina trayendo todo; el soporte, solo a sus dueños.
// Si no hay solicitante (GET, migraciones, imprimir sin dueño),
// se carga total. Ver `prompts/plan_actual.md` §1.10.
Controlador::establecer_metodo('SQL64');
$nombre_app = ConfiguracionApli::NOMBRE_APP;
$contextos_carga = _contextos_para_operacion(
    $_POST['accion'] ?? '',
    $_POST['nombre_solicitante'] ?? '',
    $_GET
);
$modo_parcial = ($contextos_carga !== null);
if (Controlador::existe($nombre_app)) {
    $ok_carga = $modo_parcial
        ? Controlador::cargar_parcial($nombre_app, $contextos_carga)
        : Controlador::cargar($nombre_app);
    if (!$ok_carga) {
        // Existe en SQL64 pero no se pudo cargar. No caemos al else:
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
// migraciones aplicadas. Solo en carga total: con carga
// parcial no se puede navegar la raíz `usuarios` ni crear
// nodos nuevos que no pertenezcan al contexto.
if (!$modo_parcial) {
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

// ==== Construcción de árboles paralelos en compartidos (v76x) ====
// Marca los compartidos con `_es_compartido` y reconstruye
// sus árboles de ventas y cancelaciones con nombres
// parametrizados (hmi_<term>, hd_<term>, p_<term>). Borra
// los enlaces planos que dejó B2.1. Idempotente.
if (isset($_GET['construir_arboles_compartidos'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/construir_arboles_compartidos.php';
    $res = construir_arboles_compartidos();
    echo "Construcción de árboles paralelos en compartidos\n";
    echo "================================================\n\n";
    echo "Marcados:  " . $res['marcados'] . "\n";
    echo "Saltados:  " . $res['saltados'] . "\n";
    if (!empty($res['errores'])) {
        echo "Errores:\n";
        foreach ($res['errores'] as $e) echo "  - $e\n";
    }
    echo "\nListo.\n";
    exit;
}

// ==== Repuntado de terminales al compartido (v77f) ====
// Cambia el enlace `dueno` de cada terminal para que
// apunte a su contenedor `compartido_con_us_termX` en vez
// del nodo del dueño real. Activa el aislamiento por
// topología. Idempotente.
if (isset($_GET['repuntar_terminales_compartido'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/repuntar_terminales_compartido.php';
    $res = repuntar_terminales_compartido();
    echo "Repuntado de terminales al compartido\n";
    echo "======================================\n\n";
    echo "Repuntados:      " . $res['repuntados'] . "\n";
    echo "Ya repuntados:   " . $res['ya_repuntados'] . "\n";
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

// ==== Reparación topológica (v78n) ====
// 1. Reparar enlaces `dueño → compartido_con_<terminal>` faltantes.
// 2. Reparar viajes faltantes en el contenedor `viajes` del dueño.
// Idempotente. Solo agrega enlaces, nunca borra.
// Uso: index.php?reparar_topologia=1
if (isset($_GET['reparar_topologia'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/migrar_topologia.php';

    $r_comp = reparar_compartidos_dueno();
    $r_viajes = reparar_viajes_dueno();
    $r_emp = reparar_empresas_dueno();
    $r_pas = reparar_pasajeros_dueno();

    echo "Reparación topológica\n";
    echo "====================\n\n";

    echo "-- Enlaces dueño → compartido_con_* --\n";
    echo "   dueños procesados: " . $r_comp['duenos'] . "\n";
    echo "   enlaces agregados: " . $r_comp['reparados'] . "\n";
    foreach ($r_comp['detalles'] as $d) {
        echo "     " . $d['dueno'] . ": " . implode(', ', $d['enlaces']) . "\n";
    }

    echo "\n-- Viajes faltantes en el contenedor del dueño --\n";
    echo "   dueños procesados: " . $r_viajes['duenos'] . "\n";
    echo "   viajes agregados:  " . $r_viajes['reparados'] . "\n";
    foreach ($r_viajes['detalles'] as $d) {
        echo "     " . $d['dueno'] . ": " . implode(', ', $d['viajes']) . "\n";
    }

    echo "\n-- Contenedor `empresas` faltante --\n";
    echo "   dueños procesados: " . $r_emp['duenos'] . "\n";
    echo "   reconstruidos:     " . $r_emp['reparados'] . "\n";
    foreach ($r_emp['detalles'] as $d) {
        echo "     " . $d['dueno'] . ": " . $d['empresas'] . " empresas\n";
    }

    echo "\n-- Contenedor `pasajeros` faltante --\n";
    echo "   dueños procesados: " . $r_pas['duenos'] . "\n";
    echo "   recuperados:       " . $r_pas['reparados'] . "\n";
    foreach ($r_pas['detalles'] as $d) {
        echo "     " . $d['dueno'] . "\n";
    }

    $total_reparados = $r_comp['reparados'] + $r_viajes['reparados'] + $r_emp['reparados'] + $r_pas['reparados'];
    if ($total_reparados > 0) {
        guardar_ambos($nombre_app);
        echo "\nGuardado.\n";
    }
    echo "\nListo.\n";
    exit;
}

// ==== Reparación de árboles hmi/hd/p (v78z) ====
// Ejecuta el comando app:reparar_arboles. Reconstruye
// las aristas `hmi` de los contenedores de árbol
// (ventas, rendiciones, liquidaciones, cancelaciones)
// que el bug §1.5 borró. Idempotente. Sin chequeo de
// modo pruebas (se usa en producción, como las otras
// reparaciones manuales).
// Uso: index.php?reparar_arboles=1
if (isset($_GET['reparar_arboles'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $res = Controlador::ejecutar_comando('app:reparar_arboles');

    if (!is_array($res)) {
        echo "ERROR: el comando app:reparar_arboles no devolvió un resultado válido.\n";
        exit;
    }

    echo "Reparación de árboles\n";
    echo "=====================\n\n";
    echo "Cambios aplicados: " . $res['cambios'] . "\n";
    echo "Por categoría:\n";
    foreach ($res['por_categoria'] as $cat => $n) {
        echo "  $cat: $n\n";
    }
    echo "\nDetalles:\n";
    foreach ($res['detalles'] as $d) {
        echo "  - $d\n";
    }
    if ($res['cambios'] > 0) {
        echo "\nGuardado.\n";
    }
    echo "\nListo.\n";
    exit;
}

// ==== Migración topológica (v78f) ====
// 1. Crea el enlace us_Administrador → usuarios.
// 2. Asegura publico en cada soporte.
// 3. Repunta el enlace `dueno → soporte` al publico del
//    soporte (evita la fuga cruzada entre dueños que
//    comparten un mismo soporte).
// Idempotente.
//
// Uso: index.php?migrar_topologia=1
if (isset($_GET['migrar_topologia'])) {
    header('Content-Type: text/plain; charset=utf-8');
    require_once __DIR__ . '/miscelaneas/migrar_topologia.php';

    $res_admin = migrar_topologia_admin($nombre_app);
    $res_soporte = migrar_topologia_soporte($nombre_app);

    echo "Migración topológica\n";
    echo "====================\n\n";

    echo "-- us_Administrador → usuarios --\n";
    if (!empty($res_admin['errores'])) {
        foreach ($res_admin['errores'] as $e) echo "  ERROR: $e\n";
    } elseif ($res_admin['ya_existia']) {
        echo "  ya existía\n";
    } else {
        echo "  creado\n";
    }

    echo "\n-- Dueño → soporte (repuntado al publico) --\n";
    echo "  soportes procesados: " . $res_soporte['soportes'] . "\n";
    echo "  repuntados:          " . $res_soporte['repuntados'] . "\n";
    echo "  ya estaban OK:       " . $res_soporte['ya_ok'] . "\n";
    if (!empty($res_soporte['errores'])) {
        echo "  Errores:\n";
        foreach ($res_soporte['errores'] as $e) echo "    - $e\n";
    }

    guardar_ambos($nombre_app);
    echo "\nGuardado.\nListo.\n";
    exit;
}
// (bloque ?migrar_a_sql64 movido antes de la carga normal, v78e v2)
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
// Fase B3.1: el chequeo del admin se hace por ID especial.
// Solo en carga total: el bootstrap del admin necesita la
// raíz `usuarios` completa, que en carga parcial no está.
if (!$modo_parcial) {
    $raiz_usuarios = Nodo::nodo_por_id('usuarios');
    $nodo_admin_existente = Nodo::nodo_por_id('us_' . ConfiguracionApli::NOMBRE_ADMIN);
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
        // Fase B3.1: chequeo por ID especial.
        if (!Nodo::nodo_por_id('us_' . ConfiguracionApli::NOMBRE_ADMIN)) {
            $id_especial_admin_cred = 'us_' . ConfiguracionApli::NOMBRE_ADMIN;
            $nodo_admin_cred = Nodo::crear_con_dato_e_id(ConfiguracionApli::NOMBRE_ADMIN, $id_especial_admin_cred);
            if ($nodo_admin_cred) {
                $nodo_admin_cred->_adyacente_en(Nodo::crear_con_dato(password_hash(Conf::CODIGO_ADMIN, PASSWORD_DEFAULT)), 'codigo_hash');
                $raiz_cred->_adyacente_en($nodo_admin_cred, ConfiguracionApli::NOMBRE_ADMIN);
            }
        }
    });
}

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
    // Fase 1c.2: pasar el dueño si viene en el GET, para que
    // la búsqueda de la venta use su contexto en carga parcial.
    generar_impresion($tipo, $_GET['id_venta'] ?? '', $dni_filtro, $numero_cupon, $_GET['dueno'] ?? '');
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