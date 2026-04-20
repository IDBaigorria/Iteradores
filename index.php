<?php
include("Nodos/Nodo.php");
include("miscelaneas/benchmark.php");
include("Controlador/Controlador.php");
include_once("Nodos/NodoElectrico.php");
use Iteradores\Nodos\Nodo;
use Iteradores\Nodos\NodoElectrico;
use Iteradores\Controlador\Controlador;
// Caso 0: se llama sin ningun parametro(crea nodo vacio completamente valido):
/*$nodo0= Nodo::nodo();
echo $nodo0->dato(); // "null"
echo $nodo0->id(); //0

// Caso 1: se le pasa un parámetro no Nodo (crea un nodo con el dato pasado por parametro)
$nodo1=Nodo::nodo("Soy el nodo 1");
echo $nodo1->dato(); // "Soy el nodo 1"
echo $nodo1->id();//1

// Caso 2: se le pasa un parametro que es un nodo (no crea ningun nodo, devuelve el mismo nodo)
$nodo2=Nodo::nodo($nodo1);
echo $nodo2->dato(); // "soy el nodo 1"
echo $nodo2->id(); //1

// Caso 3: se le pasa un parametro no Nodo y un segundo parametro por referencia (crea una
// nueva instancia de Nodo con el dato pasado en el primer parametro. Además asigna un valor
// booleano al segundo parámetro para que se pueda verificar si el primer parametro era un Nodo o no.
$esNodo=null;
$nodo3 = Nodo::nodo("soy nodo 3", $esNodo);
if ($esNodo){
     echo "el parametro de entrada era un nodo";
}else{
    echo "el parametro de entrada no era un nodo"; // Imprime esto
}

echo $nodo3->id();//2

// Caso 4: se le pasa un parametro Nodo y un segundo parametro por referencia (crea una
// nueva instancia de Nodo con el dato pasado en el primer parametro. Además asigna un valor
// booleano al segundo parámetro para que se pueda verificar si el primer parametro era un Nodo o no.
$esNodo=null;
$nodo3 = Nodo::Nodo($nodo3, $esNodo);
if ($esNodo){
    echo "el parametro de entrada era un nodo"; // Imprime esto
}else{
       echo "el parametro de entrada no era un nodo";
}
echo $nodo3->id();//2*/
echo "Pruebas Adyacentes<br>";
/*echo "_adyacente<br>";
$nodo = Nodo::crear();
$otro1 = Nodo::crear_con_id("ejemplo");
$otro2 = Nodo::crear_con_id("otro_ejemplo");

$enlace1=$nodo->_adyacente($otro1); // crea enlace "ejemplo" a $otro1
$enlace2=$nodo->_adyacente($otro2); // crea enlace "otro_ejemplo" a $otro2
$enlace3=$nodo->_adyacente($otro1); // crea enlace "ejemplo.1" a $otro1

echo "En el enlace ".$enlace1." se agrego el nodo ".$nodo->adyacente($enlace1)->id()."<br>"; //ejemplo / ejemplo
echo "En el enlace ".$enlace2." se agrego el nodo ".$nodo->adyacente($enlace2)->id()."<br>"; //otro_ejemplo / otro_ejemplo
echo "En el enlace ".$enlace3." se agrego el nodo ".$nodo->adyacente($enlace3)->id()."<br>"; //ejemplo.1 / <ejemplo></ejemplo>
*/
/*echo "_adyacente_en<br>";
$nodoA = Nodo::crear_con_dato("A");
$nodoB = Nodo::crear_con_dato("B");

// asigno nodoB como adyacente de nodoA bajo el enlace "conecta"
$nodoA->_adyacente_en($nodoB, "conecta");
echo $nodoA->adyacente("conecta")->dato(); //imprime "B"
*/
/*
echo "adyacente<br>";
$n1 = Nodo::crear_con_dato("A");
$n2 = Nodo::crear_con_dato("B");
$n1->_adyacente_en($n2, "enlaceAB");

$ady = $n1->adyacente("enlaceAB");
if ($ady) echo "Nodo adyacente: ".$ady->dato();//B
*/
/*
echo "adyacentes<br>";
$nodo = Nodo::crear();
$nodo->_adyacente_en(Nodo::crear_con_dato_e_id("hola", "Id_hola"), "enlace hola");
$nodo->_adyacente_en(Nodo::crear_con_dato_e_id("chau", "Id_chau"), "enlace chau");
$todos = $nodo->adyacentes();
if ($todos !== null) {
    foreach ($todos as $enlace => $ady) {
        echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	       unset($todos[$enlace]); //no modifico los enlaces en el nodo original
    }
}
echo "compruebo eliminacion en resultado<br>";
foreach ($todos as $enlace => $ady) {
    echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
}
echo "comprobacion nuevo resultado<br>";
$todos2 = $nodo->adyacentes();
foreach ($todos2 as $enlace => $ady) {
   echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
}*/
/*
echo "eliminar_enlace<br>";
$nodo = Nodo::crear_con_id("nodo");
$otro = Nodo::crear_con_id("otro");
$nodo->_adyacente_en($otro, "A");
echo "Se agrego el nodo ".$nodo->adyacente("A")->id()."<br>";
$eliminado = $nodo->eliminar_enlace("A");
if ($eliminado !== null) {
    echo "Se eliminó el nodo con ID: " . $eliminado->id() ."<br>";// Imprime: "otro"
}
echo "Comprobación de que realmente se elimino<br>";
$ady=$nodo->adyacente("A");
if (!$ady){
		echo "No existe adyacente en 'A'"; //imprime esto
}else{
		echo "Hasta aca no llega";
}
*/
/*
echo "eliminar_enlaces";
$nodo = Nodo::crear_con_id("nodo");
$otroA = Nodo::crear_con_id("otroA");
$otroB = Nodo::crear_con_id("otroB");
$nodo->_adyacente_en($otroA, "A");
$nodo->_adyacente_en($otroB, "B");
echo "Se agregaron enlaces: <br>";
$todos = $nodo->adyacentes();
if ($todos) {
    foreach ($todos as $enlace => $ady) {
        echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
    }
}
$copia = $nodo->eliminar_enlaces();
echo "Se aliminaron enlaces: <br>";
if ($copia){
	  foreach ($copia as $enlace => $ady) {
      echo "Enlace: $enlace, Nodo ID: " . $ady->id() . ", Nodo Dato: ". $ady->dato() ."<br>";// imprimo
	  }
}
echo "Comprobacion<br>";
$todos2=$nodo->adyacentes();
if ($todos2){
		echo "Aún tiene adyacentes, algo falló";
} else {
		echo "No tiene ningun adyacente"; //imprime esto
}*/
/*
echo "por_cada_adyacente_ejecutar<br>";
$nodo = Nodo::crear();
$nodoA = Nodo::crear_con_dato("A");
$nodoB = Nodo::crear_con_dato("B");
$nodo->_adyacente_en($nodoA, "conectaA");
$nodo->_adyacente_en($nodoB, "conectaB");

// ejecuta una función sobre cada adyacente
$resultados = $nodo->por_cada_adyacente_ejecutar(function($nodo, $enlace) {
    return "hay nodo con dato: " . $nodo->dato();
});

if ($resultados){
	  foreach ($resultados as $enlace => $resultado) {
      echo "En el enlace '$enlace' $resultado <br>";
	  }
}*/
//imprime
//En enlace 'conectaA' hay nodo con dato:A
//En enlace 'conectaB' hay nodo con dato:B
/*
echo "tiene_adyacente_a<br>";
$n1 = Nodo::crear_con_dato("A");
$n2 = Nodo::crear_con_dato("B");

$n2->_adyacente_en($n1, "enlaceBA");

if ($n1->tiene_incidente_a($n2)) {
    echo "A es adyacente de B";
}*/
/*
echo "tiene_incidente_a<br>";
$nA = Nodo::crear_con_dato("A");
$nB = Nodo::crear_con_dato("B");

$nB->_adyacente_en($nA, "enlaceBA");

if ($nA->tiene_incidente_a($nB)) {
    echo "B es incidente de A";
}
*/
/*
echo "tiene_adyacente<br>";
$nodo = Nodo::crear();
if ($nodo->tiene_adyacente()) {
    echo "El nodo tiene adyacentes<br>";
}else{
    echo "El nodo no tiene adyacentes<br>";//imprime esto
}
$otroNodo = Nodo::crear();
$nodo->_adyacente($otroNodo);
if ($nodo->tiene_adyacente()) {
    echo "El nodo tiene adyacentes<br>";//imprime esto
}else{
    echo "El nodo no tiene adyacentes<br>";
}*/
/*
echo "tiene_incidente<br>";
$nodo = Nodo::crear();
if ($nodo->tiene_incidente()) {
    echo "El nodo tiene conexiones entrantes.<br>";
}else{
    echo "El nodo no tiene conexiones entrantes.<br>";//imprime esto
}
$otroNodo= Nodo::crear();
$otroNodo->_adyacente($nodo);
$otroNodo->_adyacente($nodo);
if ($nodo->tiene_incidente()) {
    echo "El nodo tiene conexiones entrantes.<br>"; //imprime esto
}else{
    echo "El nodo no tiene conexiones entrantes.<br>";
}*/
/*
echo "cantidad_adyacentes<br>";
$nodo = Nodo::crear();
$otro1 = Nodo::crear();
$otro2 = Nodo::crear();
$nodo->_adyacente_en($otro1, "X");
$nodo->_adyacente_en($otro2, "Y");
echo $nodo->cantidad_de_adyacentes(); // 2
*/

/*echo "cantidas_de_incidentes<br>";
$nodo = Nodo::crear();
$otro1 = Nodo::crear();
$otro2 = Nodo::crear();
$otro1->_adyacente_en($nodo, "X");
$otro2->_adyacente_en($nodo, "X");
echo $nodo->cantidad_de_incidentes(); // 2*/
/*
echo "validar_nombre_enlace<br>";
$nombre1="23";
$nombre2="veintitres";
$nombre3=23;
$nombre4="0";
if (Nodo::validar_nombre_enlace($nombre1)) {
		echo ("el nombre de enlace $nombre1 es válido<br>");
}else{
    Nodo::_error("Nombre de enlace $nombre1 es inválido");
}
if (Nodo::validar_nombre_enlace($nombre2)) {
		echo ("el nombre de enlace $nombre2 es válido<br>");
}else{
    Nodo::_error("Nombre de enlace $nombre2 es inválido");
}
if (Nodo::validar_nombre_enlace($nombre3)) {
		echo ("el nombre de enlace $nombre3 es válido<br>");
}else{
    Nodo::_error("Nombre de enlace $nombre3 es inválido");
}
if (Nodo::validar_nombre_enlace($nombre4)) {
		echo ("el nombre de enlace $nombre4 es válido<br>");
}else{
    Nodo::_error("Nombre de enlace $nombre4 es inválido");
}
*/
//global $nodos;
//pruebas de rendimiento
/*function stressTest($cantidad) {
    echo "<br>--- Stress test con $cantidad nodos ---<br>";
    $nodos = [];

    benchmark("Crear $cantidad nodos", function () use (&$nodos, $cantidad) {
        for ($i = 0; $i < $cantidad; $i++) {
            $nodos[] = Nodo::crear();
        }
    });
/*
    benchmark("Crear enlaces", function () use (&$nodos) {
        foreach ($nodos as $i => $nodo) {
            if ($i > 0) {
                $nodo->_adyacente_en($nodos[$i - 1], "prev", true);
            }
        }
    });

    benchmark("Recorrer enlaces", function () use (&$nodos) {
        $total = 0;
        foreach ($nodos as $nodo) {
            $res = $nodo->por_cada_adyacente_ejecutar(fn($n, $e) => 1);
            if ($res) {
                $total += array_sum($res);
            }
        }
        echo "Total recorridos: $total <br>";
    });*/
/*}
function stressTest2($cantidad) {
    echo "<br>--- Stress test con $cantidad nodos ---<br>";
    $nodos = [];

    benchmark("Crear $cantidad nodos", function () use (&$nodos, $cantidad) {
        for ($i = 0; $i < $cantidad; $i++) {
            $nodos[] = Nodo::nodo2("nodo_$i");
        }
    });
/*
    benchmark("Crear enlaces", function () use (&$nodos) {
        foreach ($nodos as $i => $nodo) {
            if ($i > 0) {
                $nodo->_adyacente_en($nodos[$i - 1], "prev", true);
            }
        }
    });

    benchmark("Recorrer enlaces", function () use (&$nodos) {
        $total = 0;
        foreach ($nodos as $nodo) {
            $res = $nodo->por_cada_adyacente_ejecutar(fn($n, $e) => 1);
            if ($res) {
                $total += array_sum($res);
            }
        }
        echo "Total recorridos: $total <br>";
    });*/
/*}
// Ejecutamos con distintos tamaños
stressTest(1000);
//stressTest2(1000);
stressTest(10000);
//stressTest2(10000);
stressTest(100000);
//stressTest2(100000);
stressTest(200000);
//stressTest2(200000);
stressTest(1000000);
//stressTest2(1000000);*/
function probarSQL() {
    $nombre = "test_superestructura_JSON";

    echo("=== Prueba De guardar nodos electricos ===");
    //echo("<br/>Metodo predeterminado: ".Controlador::$metodo);
    echo("<br/>//0. Cambiar metodo");
    //Controlador::establecer_metodo("XML");
   // echo("<br/>Metodo predeterminado: ".Controlador::$metodo);
    echo("<br/>// 1. Limpiamos la superestructura actual");
    //Nodo.vaciar_todos();

   echo("<br/> // 2. Creamos algunos nodos");
    $n1 = NodoElectrico::crear_con_dato("Nodo 1");
    $n2 = NodoElectrico::crear_con_dato("Nodo 2");
    $n3 = NodoElectrico::crear_con_dato("Nodo 3");

    echo("<br/>// 3. Creamos enlaces entre ellos");
    $n1->_adyacente_en($n2, "enlace_12");
    $n2->_adyacente_en($n3, "enlace_23");
    $n3->_adyacente_en($n1, "enlace_31");

    echo("Nodos y enlaces creados.");
    Nodo::imprimir_superestructura();
   echo("<br/> // 4. Guardar la superestructura");
    $guardado = Controlador::guardar($nombre);
    echo("Guardado:".$guardado);

   echo("<br/> // 5. Verificar existencia");
    echo"¿Existe después de guardar?:";
    if (Controlador::existe($nombre)){
        echo "si";
    }else{
        echo "no";
    }
    

    $n3->_adyacente_en(NodoElectrico::crear_con_dato("soy nodo n4"), "enlace 34");
    Nodo::imprimir_superestructura();
       echo("<br/> // 7. Cargar desde IndexedDB");
    $cargado = Controlador::cargar($nombre);
    echo("Cargado:". $cargado);
    Nodo::imprimir_superestructura();
        echo("<br/>// 8. Mostrar nodos cargados");
/*   const todos = Nodo.todos();
    console.log("Nodos cargados:");
    for (const [id, nodo] of Object.entries(todos)) {
        console.log(`ID: ${id}, dato: ${nodo.dato()}`);
    }*/

        echo("<br/>// 9. Probar eliminación");
    $eliminado = Controlador::eliminar($nombre);
    echo("Eliminado:");
    if ($eliminado){
        echo "si";
    }else{
        echo "no";
    }
       echo("<br/> // 10. Verificar que ya no exista");
    $existe2 = Controlador::existe($nombre);
    echo("¿Existe después de eliminar?:". $existe2);
    
    if ($existe2){
        echo "si";
    }else{
        echo "no";
    }
        echo("<br/>// 10. Verificar que ya no exista");
    $existe3 = Controlador::existe("klasdfm");
    echo("¿Existe después de eliminar?:".$existe3);
    if ($existe3){
        echo "si";
    }else{
        echo "no";
    }
    echo("=== Fin de la prueba ===");
    
   //Nodo::imprimir_errores();
   //Nodo::imprimir_alertas();
}


function probarNodoElectrico(){
    echo "probar nodo electrico";
    $n1=NodoElectrico::crear();
    $n2=NodoElectrico::crear_con_dato("mama");
    $n3=NodoElectrico::crear_con_id("ma1");
    $n4=NodoElectrico::crear_con_dato_e_id("mama2", "ma2");
    $n5=NodoElectrico::nodo($n4);
    echo "oo".$n5->id();
    $n1->_adyacente($n2);
    $n1->_adyacente($n2);
    $n1->_adyacente_en($n2, "un enlacea");
    $n1->_adyacente_en($n3, "un enlaceb");
    $n3->_adyacente_en($n2, "un enlacea");
    echo "<br/>ad".$n1->cantidad_de_adyacentes()."<br/>";
    echo "<br/>ad".$n3->cantidad_de_adyacentes()."<br/>";
    echo "<br/>in".$n1->cantidad_de_incidentes()."<br/>";
    echo "<br/>in".$n3->cantidad_de_incidentes()."<br/>";
   // $n1->eliminar_adyacente("2.1");
    //$n1->eliminar_adyacentes();
    $res=$n1->por_cada_adyacente_ejecutar(function($nodo,$enlace){
        echo "<br/>".$nodo->id()."<br/>";
        return $nodo->id();
    });
    echo "<br/>Encontre: <br/>";
    foreach ($res as $enlace=>$id){
        echo "<br/>".$enlace."=>".$id;
    }

     $res2=$n2->por_cada_incidente_ejecutar(function($nodo,$enlace){
        echo "<br/>".$nodo->id()."<br/>";
        return $nodo->id();
    });
    echo "<br/>EncontreI: <br/>";
    foreach ($res2 as $idincidente=>$incidentes){
        echo "<br/>".$idincidente."=>";
        foreach ($incidentes as $enlace=>$id){
            echo "<br/>".$enlace."=>".$id;
        }
    }
    
    if ($n4->tiene_adyacente()){
         echo "<br/>tiene adyacente";
    }else{
        echo "<br/>no tiene adyacente";
    }
        if ($n4->tiene_incidente()){
         echo "<br/>tiene incidente";
    }else{
        echo "<br/>no tiene incidente";
    }

    if ($n1->tiene_adyacente_a($n2)){
         echo "<br/>tiene adyacente a";
    }else{
        echo "<br/>no tiene adyacente a";
    }
        if ($n2->tiene_incidente_a($n1)){
         echo "<br/>tiene incidente a";
    }else{
        echo "<br/>no tiene incidente a";
    }
    $ady=$n1->adyacentes();
    $inc=$n2->incidentes();
    /*var_dump($ady);
    var_dump($inc);*/
    foreach ($ady as $enlace => $nodo) {
        echo "<br/>".$enlace."=>".$nodo->id();
    }
     foreach ($inc as $idincidente => $fases) {
        echo "<br/>idin: ".$idincidente;
        foreach ($fases as $enlace => $nodo) {
            echo "<br/>".$enlace."=>".$nodo->id();
         }
    }
    NodoElectrico::imprimir_superestructura();
}
try {
    probarSQL();
} catch (\Throwable $th) {
    
    Nodo::_error("error en probarNodoElectrico");
    throw $th;
}

Nodo::imprimir_errores();
Nodo::imprimir_alertas();

?>