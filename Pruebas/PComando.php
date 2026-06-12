<?php
// ============================================================
// TEST DE COMANDO (PHP)
// ============================================================
// Estado de implementación:
// ✅ = implementado y probado
// ⏳ = pendiente / incompleto
// ❌ = no implementado
// ============================================================
require_once "Nodos/NodoElectrico.php";
require_once "Nodos/Nodo.php";
require_once "Controlador/Controlador.php";
require_once "Configuracion/Entorno.php";
require_once "Configuracion/Configuracion.php";
require_once "Comandos/Comando.php";

use Iteradores\Nodos\NodoElectrico;
use Iteradores\Nodos\Nodo;
use Iteradores\Controlador\Controlador;
use Iteradores\Configuracion\Entorno;
use Iteradores\Configuracion\Conf;
use Iteradores\Comandos\Comando;

// Forzar modo desarrollo si no está definido
if (!Entorno::es_desarrollo()) {
    echo "⚠️ Las pruebas deberían ejecutarse en entorno DESARROLLO<br><br>";
}

// ──────────────────────────────────────────────────────────
// 7. PRUEBAS EXHAUSTIVAS DEL SISTEMA DE COMANDOS
// ──────────────────────────────────────────────────────────
/*echo "\n🔹 Pruebas del sistema de comandos<br>";

Controlador::ejecutar_prueba(function($token) {
    // 7.1 Ejecutar el comando debug:imprimir en modo CONSOLA
    echo "▶ 7.1 Ejecutar 'debug:imprimir' en modo CONSOLA<br>";
    Entorno::establecer_salida(Entorno::SALIDA_CONSOLA);
    $resultado = Controlador::ejecutar_comando('debug:imprimir');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // 7.2 Ejecutar el comando en modo HTML
    echo "▶ 7.2 Ejecutar 'debug:imprimir' en modo HTML<br>";
    Entorno::establecer_salida(Entorno::SALIDA_HTML);
    Controlador::ejecutar_comando('debug:imprimir');

    // 7.3 Intentar ejecutar un comando inexistente
    echo "▶ 7.3 Ejecutar comando inexistente<br>";
    $res_inexistente = Controlador::ejecutar_comando('comando:inexistente');
    echo "   Resultado: " . var_export($res_inexistente, true) . " (debe ser null)<br>";
    echo "   (Debe aparecer un error en el log)<br>";
    NodoElectrico::imprimir_errores();
    NodoElectrico::limpiar_errores();

    // 7.4 Probar deshacer con pila vacía
    echo "▶ 7.4 Deshacer con historial vacío<br>";
    $res_deshacer = Controlador::deshacer_ultimo();
    echo "   Resultado: " . var_export($res_deshacer, true) . " (debe ser null)<br>";
    echo "   (Debe aparecer una alerta 'No hay comandos para deshacer')<br>";
    NodoElectrico::imprimir_alertas();
    NodoElectrico::limpiar_alertas();

    // 7.5 Registrar un comando en caliente (post-inicialización)
    echo "▶ 7.5 Registrar comando en tiempo de ejecución<br>";
    $registrado = Controlador::registrar_comando(
        'prueba:eco',
        function($token, ...$args) { return implode(', ', $args); },
        null,
        false
    );
    echo "   Registro: " . var_export($registrado, true) . " (debe ser true)<br>";
    // Ejecutar el nuevo comando con argumentos
    $eco = Controlador::ejecutar_comando('prueba:eco', 'Hola', 'Mundo');
    echo "   Ejecución con argumentos: " . var_export($eco, true) . " (debe ser 'Hola, Mundo')<br>";

    // 7.6 Probar que un comando de desarrollo no se registra en producción
    echo "▶ 7.6 Bloqueo de comando de desarrollo en producción<br>";
    Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
    $registrado_prod = Controlador::registrar_comando(
        'debug:temp', function() {}, null, true
    );
    echo "   Registro en producción: " . var_export($registrado_prod, true) . " (debe ser false)<br>";
    Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

    // 7.7 Probar encolar_comando post-inicialización (registro inmediato)
    echo "▶ 7.7 Encolar comando después de inicializar (registro inmediato)<br>";
    $clase_anonima = new class implements \Iteradores\Comandos\Comando {
        public static function nombre(): string { return 'anonimo:test'; }
        public static function solo_desarrollo(): bool { return false; }
        public function ejecutar(string $token, ...$args) { return 'OK'; }
        public function reversa(): ?callable { return null; }
    };
    Controlador::encolar_comando($clase_anonima);
    $res_anonimo = Controlador::ejecutar_comando('anonimo:test');
    echo "   Resultado: " . var_export($res_anonimo, true) . " (debe ser 'OK')<br>";

    echo "✅ Pruebas de comandos completadas<br>";
});
**/

// ============================================================
// PRUEBAS EXHAUSTIVAS DEL SISTEMA DE COMANDOS v1.3.2
// ============================================================
// Cubre: parseo centralizado, ayuda automática, validación
// de argumentos, palabras reservadas y el comando
// depuracion:imprimir.
// ============================================================
/*
echo "\n🔹 Pruebas del sistema de comandos v1.3.2<br>";

Controlador::ejecutar_prueba(function($token) {
    $nodo=Nodo::crear();
    Nodo::_error("error");
    Nodo::_alerta("alerta");
    // ─── 7.1 Ejecución básica del comando depuracion:imprimir ───
    echo "▶ 7.1 Ejecutar 'depuracion:imprimir' sin argumentos (todo)<br>";
    Entorno::establecer_salida(Entorno::SALIDA_HTML);
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // ─── 7.2 Ejecutar con una sola bandera ───
    echo "▶ 7.2 Ejecutar 'depuracion:imprimir --errores'<br>";
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir', '--errores');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // ─── 7.3 Ejecutar con múltiples banderas ───
    echo "▶ 7.3 Ejecutar 'depuracion:imprimir --errores --super'<br>";
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir', '--errores', '--super');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // ─── 7.4 Solicitar ayuda con --man ───
    echo "▶ 7.4 Solicitar ayuda con 'depuracion:imprimir --man'<br>";
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir', '--man');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true, y debe mostrarse la ayuda)<br>";

    // ─── 7.5 Solicitar ayuda con --help ───
    echo "▶ 7.5 Solicitar ayuda con 'depuracion:imprimir --help'<br>";
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir', '--help');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser true)<br>";

    // ─── 7.6 Pasar un flag desconocido ───
    echo "▶ 7.6 Pasar flag desconocido '--desconocido'<br>";
    NodoElectrico::limpiar_errores();
    $resultado = Controlador::ejecutar_comando('depuracion:imprimir', '--desconocido');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser null)<br>";
    echo "   Errores generados:<br>";
    NodoElectrico::imprimir_errores();

    // ─── 7.7 Comando sin definición de parámetros ───
    echo "▶ 7.7 Registrar y ejecutar comando sin parametros()<br>";
    $clase_anonima = new class implements Comando {
        public static function nombre(): string { return 'test:simple'; }
        public static function solo_desarrollo(): bool { return false; }
        public static function descripcion(): string { return 'Comando simple.'; }
        public static function parametros(): array { return []; }
        public static function ejemplos(): array { return []; }
        public function ejecutar(string $token, array $args): mixed {
            return 'OK';
        }
        public function reversa(): ?callable { return null; }
    };
    Controlador::encolar_comando($clase_anonima);
    $resultado = Controlador::ejecutar_comando('test:simple', 'cualquier', 'cosa');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser 'OK')<br>";

    // ─── 7.8 Comando inexistente ───
    echo "▶ 7.8 Ejecutar comando inexistente<br>";
    NodoElectrico::limpiar_errores();
    $resultado = Controlador::ejecutar_comando('comando:inexistente');
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser null)<br>";
    echo "   Errores generados:<br>";
    NodoElectrico::imprimir_errores();

    // ─── 7.9 Probar bloqueo de comandos de desarrollo en producción ───
    echo "▶ 7.9 Bloqueo de comando de desarrollo en producción<br>";
    Entorno::establecer_modo(Entorno::MODO_PRODUCCION);
    $registrado = Controlador::registrar_comando('depuracion:temp', function() {}, null, true);
    echo "   Registro en producción: " . var_export($registrado, true) . " (debe ser false)<br>";
    Entorno::establecer_modo(Entorno::MODO_DESARROLLO);

    // ─── 7.10 Deshacer con historial vacío ───
    echo "▶ 7.10 Deshacer con historial vacío<br>";
    NodoElectrico::limpiar_alertas();
    $resultado = Controlador::deshacer_ultimo();
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser null)<br>";
    echo "   Alertas generadas:<br>";
    NodoElectrico::imprimir_alertas();

    echo "✅ Pruebas de comandos v1.3.2 completadas<br>";
});
*/

// ============================================================
// PRUEBAS EXHAUSTIVAS DE LOS COMANDOS DE DEPURACIÓN v1.3.2
// ============================================================
// Cubre: depuracion:imprimir, depuracion:limpiar,
// depuracion:recoleccion y sus combinaciones.
// ============================================================
/*
echo "\n🔹 Pruebas de los tres comandos de depuración<br>";

Controlador::ejecutar_prueba(function($token) {
    // ─── Preparación: generar errores y alertas ─────────────
    NodoElectrico::limpiar_errores();
    NodoElectrico::limpiar_alertas();
    NodoElectrico::_error("Error de prueba A");
    NodoElectrico::_error("Error de prueba B");
    NodoElectrico::_alerta("Alerta de prueba 1");
    NodoElectrico::_alerta("Alerta de prueba 2");
    NodoElectrico::_alerta("Alerta de prueba 3");

    // ─── 8.1 Imprimir todo ──────────────────────────────────
    echo "▶ 8.1 depuracion:imprimir (sin argumentos)<br>";
    Controlador::ejecutar_comando('depuracion:imprimir');

    // ─── 8.2 Imprimir solo errores ─────────────────────────
    echo "▶ 8.2 depuracion:imprimir --errores<br>";
    Controlador::ejecutar_comando('depuracion:imprimir', '--errores');

    // ─── 8.3 Limpiar solo alertas ──────────────────────────
    echo "▶ 8.3 depuracion:limpiar (debe quedar 2 errores)<br>";
    Controlador::ejecutar_comando('depuracion:limpiar', '--alertas');
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "   (No deberían aparecer alertas)<br>";

    // ─── 8.4 Imprimir solo alertas (vacío) ─────────────────
    echo "▶ 8.4 depuracion:imprimir --alertas (debe estar vacío)<br>";
    Controlador::ejecutar_comando('depuracion:imprimir', '--alertas');

    // ─── 8.5 Limpiar todo ──────────────────────────────────
    echo "▶ 8.5 depuracion:limpiar (limpia ambas pilas)<br>";
    Controlador::ejecutar_comando('depuracion:limpiar');
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "   (No debería haber errores ni alertas)<br>";

    // ─── 8.6 Desactivar recolección y generar mensajes ──────
    echo "▶ 8.6 Desactivar recolección de errores y generar uno<br>";
    Controlador::ejecutar_comando('depuracion:recoleccion', 'desactivar', '--errores');
    NodoElectrico::_error("Este error NO debe registrarse");
    Controlador::ejecutar_comando('depuracion:imprimir', '--errores');
    echo "   (No debería aparecer el error)<br>";

    // ─── 8.7 Reactivar recolección y generar otro ──────────
    echo "▶ 8.7 Activar recolección de errores y generar otro<br>";
    Controlador::ejecutar_comando('depuracion:recoleccion', 'activar', '--errores');
    NodoElectrico::_error("Este error SÍ debe registrarse");
    Controlador::ejecutar_comando('depuracion:imprimir', '--errores');
    echo "   (Debe aparecer un error)<br>";

    // ─── 8.8 Desactivar todo ───────────────────────────────
    echo "▶ 8.8 depuracion:recoleccion desactivar (todo)<br>";
    Controlador::ejecutar_comando('depuracion:recoleccion', 'desactivar');
    NodoElectrico::_error("No registrado");
    NodoElectrico::_alerta("No registrada");
    Controlador::ejecutar_comando('depuracion:imprimir', '--errores', '--alertas');
    echo "   (Debe aparecer solo el error anterior)<br>";

    // ─── 8.9 Reactivar todo y generar ─────────────────────
    echo "▶ 8.9 depuracion:recoleccion activar (todo)<br>";
    Controlador::ejecutar_comando('depuracion:recoleccion', 'activar');
    NodoElectrico::_error("Error final");
    NodoElectrico::_alerta("Alerta final");
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "   (Deben aparecer ambos)<br>";

    // ─── 8.10 Ayuda de cada comando ───────────────────────
    echo "▶ 8.10 Ayuda de los tres comandos<br>";
    Controlador::ejecutar_comando('depuracion:limpiar');
    Controlador::ejecutar_comando('depuracion:imprimir', '--man');
    Controlador::ejecutar_comando('depuracion:limpiar', '--help');
    Controlador::ejecutar_comando('depuracion:recoleccion', '-h');
    Controlador::ejecutar_comando('depuracion:imprimir', '--manual');
    Controlador::ejecutar_comando('depuracion:limpiar', '--ayuda');
    Controlador::ejecutar_comando('depuracion:recoleccion', '-ay');
    Controlador::ejecutar_comando('depuracion:imprimir');

    // ─── 8.11 Combinaciones inválidas ────────────────────
    echo "▶ 8.11 Combinaciones inválidas<br>";
    NodoElectrico::limpiar_errores();
    Controlador::ejecutar_comando('depuracion:recoleccion', 'invalidar');
    Controlador::ejecutar_comando('depuracion:recoleccion');
    Controlador::ejecutar_comando('depuracion:imprimir', '--inexistente');
    echo "   Errores generados:<br>";
    NodoElectrico::imprimir_errores();

    echo "✅ Pruebas de los tres comandos de depuración completadas<br>";
});*/
// ─── 9. Pruebas de comando reversible con argumentos ───
echo "\n🔹 Comando reversible con argumentos<br>";

Controlador::ejecutar_prueba(function($token) {
    echo "▶ 9.1 Ejecutar 'prueba:crear_nodo' con argumentos<br>";
    $resultado = Controlador::ejecutar_comando('prueba:crear_nodo', 'Sensor', '--capacidad=150', '--fuga=0.3');
    $resultado = Controlador::ejecutar_comando('prueba:crear_nodo', 'Sensor', '--capacidad=150', '--fuga=0.3');
    echo "   Resultado: $resultado<br>";
    // Extraer id del resultado
    preg_match('/\d+/', $resultado, $matches);
    $id_nodo = $matches[0] ?? null;
    echo "   ID del nodo creado: $id_nodo<br>";
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "▶ 9.2 Verificar que el nodo existe<br>";
    $nodo = NodoElectrico::existe($id_nodo) ? NodoElectrico::nodo_por_id($id_nodo) : null;
    echo "   Nodo obtenido: " . ($nodo ? $nodo->dato() : 'no encontrado') . "<br>";
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "▶ 9.3 Deshacer el comando (eliminar nodo)<br>";
    $deshecho = Controlador::deshacer_ultimo();
    echo "   Resultado de deshacer: $deshecho<br>";
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "▶ 9.4 Verificar que el nodo ya no existe<br>";
    echo "   Existe: " . (NodoElectrico::existe($id_nodo) ? 'sí (error)' : 'no (correcto)') . "<br>";
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "▶ 9.5 Deshacer de nuevo (pila vacía)<br>";
    NodoElectrico::limpiar_alertas();
    $resultado = Controlador::deshacer_ultimo();
    echo "   Resultado: " . var_export($resultado, true) . " (debe ser null)<br>";
   // NodoElectrico::imprimir_alertas();
    Controlador::ejecutar_comando('depuracion:imprimir');
    echo "✅ Pruebas de comando reversible completadas<br>";
});
?>