<?php
namespace Iteradores\Iteradores;
use Iteradores\Configuracion\Conf;
use Iteradores\Nucleo\Objeto;
use Iteradores\Nodos\Nodo;
include_once(".\Nucleo\Objeto.php");
include_once(".\Nodos\Nodo.php");
//namespace MyApp;
//**********************************************************************************************************//
//											Clase: Iterador
//										  Versi�n: 1.9
//											Autor: Ignacio David Baigorria
//
//*********************************************************************************************************//

//05/03/2013 V0.1: Copiando la parte alias de la clase Grafo V1.9
//17/03/2013 V0.2: Se comprobo el pefecto funcionamiento de las funciones para asignar alias y obtener los enlaces relacionados con los alias.
//18/03/2013 V0.3: Se agregaran las funciones para obtener los nodos, avanzar, agregar y eliminar 
//19/03/2013 V0.4: Se comprob� el funcoonamiento de las funciones agragadas, se depuraron 3 errores.
//			 V0.5: Se mejorar� interfaz entrada.
//25/03/2013	 : Pruebas
//15/04/2013	 : Mejoras en manejo de entradas y actual.
//29/04/2013 V0.6: Revisando entradas y actual.	
//07/07/2013 V0.7: Refactorizacion, cambio de requerimientos.
//08/07/2013     : Interface entrada
//17/07/2013 V0.8: Interface alias
//17/07/2013 V0.9: Manejo de adyacentes con alias.
//20/07/2013 V1.1.1: Cambiando dise�o la interfaz entrada para adaptarse a los nuevos requerimientos de la versi�n 1.2
//21/07/2013 V1.1.3: Cambio de dise�o en los alias,a partir de ahora seran nodos como exige la version 1.2
//			 V1.1.5: Interfaz de manejo
//			 V1.1.7: Cargar guardar.
//09/08/2013 V1.3: Comienzo versi�n
//			 V1.3.1: cambio en la interfaz creacion, se agrega iterador(noimbre)
//			 V1.3.3: Cambio en la interfaz de manejo de adyacentes con alias, se agrego avanzar o avanzar
//					 Se cambia el nombre de la funcion agregar_adyacente por _adyacente_en
//					 Se detecto error en enlace, voy a resolverlo
//10/08/2013		 Solucionado errores en avanzar y avanzar_o_avanzar
//			 V1.3.5: Cambio de requerimientos.
//13/08/2013 V1.3.7: Se agrega adyacentes() que devuelve un arreglo con todos los adyacentes.
//03/01/2017 (V1.4)
//			 V1.170103  Se revisa todo y se intenta dejar version estable.
//						Se modifico Crear(nombre) para que no permita que se creen dos Iteradores con el mismo nombre.
//04/01/2017 V1.170104  Se continua con el trabajo de revision.
//						Se modifico Crear(nombre), antes se usaba como id del nodo raiz del iterador el propio nombre del iterador, esto puede generar conflictos cuando en otro lugar del sistema se quiera utilizar el mismo nombre como id. Desde ahora el nombre sera guardado en el dato del nodo raiz, y el dientificador ser� uno generado por el sistema.
//						Se modificaron actual() y _actual($nodo)	
//05/01/2017 V1.170105  Nada
//06/01/2017 V1.170106  Se continua con el trabajo de revision.
//						Revision de la interfaz alias.
//						Se solucionan varios problemas de la interfaz alias
//						Fin de revision de la interfaz alias
//07/01/2017 V1.170107  Solucionado prolema en eliminar_adyacente(alias)
//08/01/2017 V1.170108  Pruebas con clases hijas.
//21/01/2017 V1.170121  Cambio en avanzar, ahora no delvolvera el nodo actual, devolvera true o false para indicar si tubo o no exito
//						Agregada funcion _avanzar(modo, indice)
//			 
//			(V1.5)
//			 V1.170122  Vamos a darle mas poder a las funciones adyacente(alias) y avanzar(alias), ahora podran recibir una cadena de alias separadas por un espacio desde 
//						Creadas funciones auxiliares, camino(cadena) y eliminar camino(cadena)
//			 V1.170123  comienzo nuevas implementaciones de adyacente(cadena) y avanzar(cadena)
//						Implemento un avanzar_interno().
//			V1.170124	Continuo con el avanzar_int
//						Implemento funciones y las pruebo con avanzar_int
//			V1.170130	Agrego un parametro a avanzar_int para indicarle si genera o no alertas...por defecto generar� las alertas. Adem�s se hara una peque�a modificacion para que en el caso de error vuelva a la posicion original. La misma modificacion se hara a avanzar
//
//			V1.6.170226 Vamos a agregar una funcion construir() para que permitirar construir una estructura compleja a partir de una cadena pasada como parametro. 
//			V1.7.170304 Voy a agragar dos funciones dato(camino=null) y _dato(string o int, camino=null) que devuelvan el dato en el nodo por ese camino, o agreguen un dato en el nodo por ese camino.
//						Elimino la restriccion de que los alias no pueden contener espacios en blanco. Voy a probarlo temporalmente para siempre si no hay inconveniente
//V1.7.170318	La funcion construir no funciona del todo bien. Debera agregarse un metodo para poder saltear los caracteres especiales que se utilizan para la estructura del string pasada por parametro.
//Queda pendiente
//V1.7.170618	Cambio la version del nodo
//				Cambio en validar_alias, ahora se permite que el alias sea la representacion de un entero
//V1.7.170819	Solucionado problema en eliminar_camino, aveces no eliminaba el simbolo, ahora s�.
//V1.7.171101	Cambio la version de nodo por la ultima de esta misma fecha
//				Agrego funcion nombre() que devuelve el nombre del Iterador

//	CAMBIO NOMENCLATURA 1.7.0.
//			OBJETIVOS:
//				OBJETIVO DE LA VERSION 1.7: Comprobar el buen funcionamiento de construir. Agregar una contracara a esa funcion que cree una cadena string a partir de una estructura existente que no tenga bucles de enlaces; dicha cadena debe poder ser pasada como parametro a la funcion contruir y construir debe poder recplicar la estructura a partir de dicha cadena. Destruir una estructura que no tenga bucles de enlaces.
//			HOJA DE RUTA:
//					V0.7:
//						V0.7.0: Comprobar y comprender la funcion construir
//						V0.7.1: Agregar funcion para comprobar si hay bucles de enlaces.
//						V0.7.2: Agregar funcion que construya una cadena a partir de una estructura sin bucles
//						V0.7.3: Destruir una estructura sin bucles
//V1.7.0.171105	parece que construir funciona bien. se arreglo apenas un problema
//V1.7.1.171106 agregada funcion liberar()
//				agrego include_once para la clase Arbol, esto significa que ambas clases quedan equiparadas... nose si es bueno.. pero necesito utilizar un arbol para verificar si la estructura tiene bucles y no quiero hacer otro.
//				hay_bucle(alias) parece que funciono!
//V1.7.2.171107 agrego arbolear(alias)
//				cambio hay_bucle(alias) por es_arboleable(alias) que, en caso de que la estructura pueda formatearse como un arbol, devuelve la raiz de dicho arbol.
//				modifico eliminar_adyacente para que en vez de devolver true devuelva el nodo eliminado de la estructura
//				agrego destruir() y sus subfunciones. parece que funciona
//1.7.3.171107	agrego copiar y copiar_y_destruir. Parece que funcionan
//1.7.3.171122	include el ultimo arbol
//1.7.3.180321	include el ultimo arbol 
//1.7.4.180423	destruir_estructuras_adyacentes() que intenta destruir a los adyacentes
//				pruebas superadas

/*/////////////////////////////////////ERROR DE SEGURIDAD DETECTADO///////////////////////////////////////////////////
$ib=Bibliotecario::crear("biblio","a");


$ibc=Bibliotecario::cargar("biblio");

echo "\r\n"."DATO AQUI: ".$ib->dato()."\n";

$ib->actual()->imprimir();
$ibc->hmi();
$ib->actual()->imprimir();

echo "DATO ATQUI: ".$ib->dato()."\n";

FIJESE QUE SI SE CARGA UN MISMO ITERADOR DOS VECES SE PUEDE CONTROLAR EL MISMO ITERADOR DESDE DOS VARIABLES DISTINTAS QUE PUEDEN ESTAR EN CONTEXTOS TOTALEMTNTE DISTINTISO. ESTO ES UN GRAVE PROBLEMA QUE HABRA QUE SOLUCIONAR. HABRA QUE COMPROBAR COMO MINIMO QUE EL ITERADOR NO PUEDA SER CARGADO SI YA HABIA SIDO CREADO EN LA MISMA EJECUCION, LO MISMO SI SE INTENTAMTA CARGAR DOS VECES
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

PARA LA PROXIMA VERSION ADEMAS: agregar sistema para clonarse el propio iterador

V1.8.0.180502	hoja:
					0	marcar como ocupado/desocupado
					1	crear cargar	marcar como ocupado
					2	verificar si esta ocupado
					3	imlementar semaforos en copiar, y demas funciones que utilicen arbolado
					4	verificar clases al momento de crear cargar
					5	clonar iterador
				
				ocupar desopcupar ocupado
				pruebas superadas
				crear cargar	marcar como ocupado
				pruebas superadas
V1.8.2.180502	verificar si esta ocupado
				pruebas superadas
/////////////////////////////////////SOLUCIONADO ERROR DE SEGURIDAD ENCONTRADO EN 1.7.4.180423/////////////////////////
V1.8.3.180502	clonar!
				cambio hoja de ruta
				semaforos en arbolear y todos los que dependen de el, todavia no termine
				pruebas de semaforos superadas

/*//////////////////////////////////////ERROR DE SEGURIDAD DETECTADO///////////////////////////////////////////////////
/*esto no puede ser permitido:	
	$i=Iterador::crear("iterador i");
	$i->desocupar();
	$a=Arbol::cargar("iterador i");
luego se generan errores ya que en el ejemplo se podria intentar asignarle una raiz a un iterador que no es de la clase 
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				cambio hoja de ruta, voy a intentar solucionar ese problema antes de avanzar con el clonar
V1.8.4.180503	verificar clases al momento de crear cargar//abandono esto, lo dejo para mas adelante, ncesito hacer el clonar!! urgentemente
				pruebas clonar superadas, avanzo con las infunciones interrogativas 
				superadas pruebas, ahora tengo que redefinir el clonar en las clases herederas
/////para las proximas versiones:
	sistema de marcas para volver a puntos anteriores
	la cuestion seguridad de gerarquias de los iteradores en cargar y clonar... tema complicado
	ademas de semaforos en las funciones que utilizan arboleado se podra pasar una lista de nodos finales, en vez de ser enlaces que no se pueden seguir es una lista de textos que si alguno es encontrado como dato de un nodo entonces ese no se sigue ningun enlace a partir de ese nodo.
	sistema para clonar la estructura... con semaforos y nodos finales
	agregar un nodo con enlace "datos" y ina interfaz para acceder a el, _datos podria recibir una string con estructura construible. la finalidad de este nodo seria que el mismo pueda ser copiado al momento de clonar el iterador.
	ademas se puede agregar otro nodo "datos_individuales"con su correspondiente iterfaz igual a la anterior, la unica diferencia es que datos individuales no se copiarian al momento de clonar
V1.8.5.180506		hoja:	
						5.insertar datos
						6.devolver nodo con datos.. devolver tipo con ruta de avanzar
						7.destruir datos
						8.modificar clonar

				insertar datos 
				_datos(datos,ruta=null)
				_datos_individuales(datos,ruta=null)
				pruebas superadas
V1.8.6.180506	datos(ruta=null)
				datos_individuales(ruta=null)
				pruebas superadas!
V1.8.7.180506	destruir datos
				destruir datos individuales
				pruebas superadas!
V1.8.8.180506	modificar clonar
				pruebas superadas!!
V1.8.9.180508	minihoja:	encontrar error en construir
							metodo para agregar barras para saltear caracteres especiales de construir
							integrar a copiar
				todas las pruebas superadas!!
V1.8.9.180520	cambiar copiar() por copiar_estructura()
				agrego es_iterador
				agrego eliminar_adyacentes();
V1.9.0.180530	cargo ultima version de Nodo
				ahora desocupar() no va a liberar los nodos a los que apunta, para permitir que el proximo qe utilice el iterador pueda seguir utilizandolo desde el ultimo estado en el que estuvo. Si se desea que el iterador pierda las direcciones a donde apunta debe utilizarse liberar(), antes de desocupar el iterador
V1.9.0.180606	refactorizo _alias
V1.9.0.180617	
V1.9.1.180619	destruir
				cambio destruir por destruir_estructura
V1.9.1.180819   descarto todo 180708
V1.9.2.181111	cambio version de Nodo por la version refactorizada
	COMIENZO REFACTORIZACION BETA
V1.9.2.190106	comienzo refactorizo inteface de carga creacion y  destruccion
				refactorizo iterfaz de marca de ocupado
				descubierto error en el concepto de ocupar desocupar. Al ser persistente el enlace entre el nombre de la variable y el iterador, luego de desocupar el iterador, si el mismo es vuelto a ocupar por otra variable la variable anterior tambien podra hacer uso de el. Lo soluciono eliminando la propiedad que guarda la raiz del iterador en la variable.
				crear, cargar, iterador pruebas superadas
				ocupado, ocupar, desocupar, pruebas superadas
V1.9.2.190107	nombre, interfaz actual
				pruebas superadas
V1.9.3.190113	problema de seguridad detectado. Los iteradores deben permitir ser cargado solamente desde la misma	clase que fue creado. Para solucionarlo vamos a guardar en el nodo "raiz_cuerpo" del iterador un	enlase a un nodo que contenga el nombre de la clase.
V1.9.3.190211	probada la solucion se llega a la conclucion de que se generaran nodos de mas al tener cada iterador un nodo a su clase, la solucion debera encontrar la manera de que los 'nodos clase' sean unicos, es decir, alla uno por cada clase de iteradores.
				se propone como opcion agregar una funcion estatica pretegida para que las clases puedan registrar
				registrar_clase
				pruebas superadas
				crear_interno
				pruebas_superadas
				crear
				pruebas_superadas

				mini hoja:
					crear_interno
					crear
					cargar_interno
					cargar
					interador_interno
					iterador
					destruir_interno
					destruir

				comienzo trabajo
				crear_interno
				pruebas_superadas
				crear
				pruebas_superadas

				cargar_interno
				pruebas superadas
				cargar
				pruebas superadas

V1.9.3.190214	iterador_interno
				pruebas superadas
				iterador
				pruebas superadas
				destruir se deja para mas adelante

				modifico iterador_interno para que devuelva en una variable si creo o no un iterador nuevo
V1.9.3.190217	agrego es_elemento_valido, lo cual me va a permitir hacer la modificacion anterior sin problemas
				agrego nodo(elemento, es_nodo) pruebas superadas
				crear pruebas superadas
				destruir pruebas superadas
V1.9.3.190219	cargar_interno pruebas superadas
				cargar pruebas superadas.
				iterador_interno pruebas superadas
				iterador pruebas superadas
			finalizada interfas de creacion carga y destruccion
V1.9.3.190220
			comienzo interfaz de manejo de alias
				es_alias_valido pruebas superadas
				_alias pruebas superadas
				eliminar_alias pruebas superadas
				_varios_alias pruebas superadas
				_eliminar_todos_los_alias pruebas superadas
				modifico destruir() pruebas superadas
				enlace() pruebas superadas
			comienzo interfaz avanzar
				camino pruebas superadas
				eliminar_camino pruebas superadas
				avanzar_interno pruebas superadas
				avanzar pruebas superadas
V1.9.3.190221a	inicializado procesode primariacion interno de las funciones, esto es utilizar en menor medida los procesos que tengan sentencias
			COMPRABADO QUE SE ACELARA ENTRE UN 17 Y 19 POR CIENTO IMPLEMENTANDO LA PRIMARIZACION, TAMBIEN SE DEBE PRODUCIR UN AHORRO DE MEMORIA, NO SABEMOS CUANTO.
				_avanzar pruebas superadas
				elimino avanzar_o_avanzar
			finalizada interface AVANZAR
V1.9.3.190228	
			refactorizo las funciones que crean nodos
				nodo() pruebas superadas
				crear, cargar, iterador, pruebas superadas
				_actual, _avanzar pruebas superadas
				continuo con los que insertan nodo, 
			comienzo inerfaz adyacente
V1.9.3.190304	continuo con _adyacente_en
				pruebas superadas
				_adyacente pruebas superadas
				_adyacentes pruebas superadas
			finalizada refactorizacion de las funciones que crean nodos
V1.9.3.190305	adyacentes pruebas superadas
				adyacente pruebas superadas
				eliminar_adyacente pruebas superadas
				eliminar_adyacentes pruebas superadas
				_como_adyacente_de_nodo_en_alias pruebas superadas
				_adyacente_inverso pruebas superadas
			finalizada refactorizacion de interfaz adyacente
V1.9.3.190306
			comienzo refactorizacion interfaz dato
				_dato pruebas superadas
				dato pruebas superadas
			finalizada interfaz Dato
		FINALIZADAS FUNCIONES DE NIVEL 1 (las 0 son de la clase Nodo)
V1.9.4.190328	
		comienzo refactorizacion usando la nueva estructura planteada en "Estructura iteradores 2019.png"
V1.9.4.191106
		retomo despues de mucho tiempo, busco diferencias entre las dos ultimas versiones
		agrego _alias_permitidos
		modifico integrar_iterador para q registre los alias permitidos
		modifico enlace (ahora mira si es un alias permitido)
V1.9.4.191204
		modifico avanzar_interno para que guarde los caminos
		solucionados varior errores en avanzar_interno pruebas superadas
V1.9.4.191208
		modifico avanzar_interno ahora solo guarda los caminos si tubo exito recorriendolos, esto es para no guardar caminos con errores de sintaxis
		arbolear es_arboleable refactorizo pruebas superadas
		liberar pruebas superadas
		destruir_estructura pruebas superadas
V1.9.5.191213
		contruir_escapar pruebas superadas
		construir pruebas superadas
		falta comprobar funcionamiento semaforos
		ver que pasa cuando se usan alias
V1.9.5.191215
		problema con semaforos solucionado, agregar funcion en Nodo para eliminar nodos autoenlazados (nsemaforos) - agregada, ahora se elimina, pruebas superadas
		debo modificar las funciones de la interfaz alias para que guarden informacion de a que enlace le corresponde un alias, lo contrario q guargaba hasta ahora que era, a que alias le correspondia tal enlace. Se mantendran las dos cosas
		pruebas superadas
		arreglado problema en arbolear y es_arboleable con respecto a los alias, pruebas superadas
		refactorizo copiar_estructura pruebas superadas
		solucionado problema al destruir estructuras
V1.9.5.191217
		agregando camino a las funciones arboleado, pruebas superadas
		agregando camino a las funciones construir.. destruir_estructura destruir_estructuras_adyacentes pruebas superadas
		agregado camino a construir(), pruebas superadas
		agregado camino a compiar_y_destruir(), pruebas superadas
		agregado camino a compiar_estructura(), pruebas superadas
		FINALIZADA REFACTORIZACION DE LINTERFAZ ESTRUCTURA
V1.9.6.191222
		comienzo refactorizacion de la interfaz de clonacion // debo hacer primero las de datos, y datos indivudales. luego retomo
		antes de hacer las de datos debo realizar cambios a avanzar_interno
		avanzar_interno pruebas superadas
V1.9.6.191228
		cambios en constrir para acelerar, pruebas superadas, los tiempos fueron reducidos a la mitad! y no se tilda...
V1.9.7.191230
		ahora si, encaro los _datos
		interface DATOS pruebas superadas
		interface DATOS INDIVIDUALES pruebas superadas
V1.9.8.191231
		comienzo con clonar
		solucionado problema en _alias
		clonar pruebas superadas.. falta probar el destruir ahora
		destruir pruebas superadas
	FINALIZADA REFACTORIZACION BETA
V1.9.8.200113
		agregados verificacion de nodos (elementos validos) en contruir
		modifico es_elemento_valido para que devuelva true o false, y no el dato como era hasta ahora. a cambio se pasa por variable el elemento y cualquier modificacion al dato se hara a travez de esa variable
		eliminada la llamada a la funcion ocupado() de varias funciones
V1.9.9.200515
		Agrego interfaz "datos temporales"
		modifico desocupar() para q elimine los datos temporales

V2.0.0.202520
	hoja de ruta:
	0.1
		activar_guardado
		guardar.. guardar_interno
		destruir
	0.2	
		modificar actual()
		cuando se asigna nodo en la creacion
		avanzar, todos
		adyacebtes.. si mueven
		clonar?
	modificar en arbol y seguir aca
	0.3
		inicio
		ultimo
		siguiente
		anterior
		volar_al(3)

V2.0.1.202520	
		guardar_visitado_interno
		destruir_visitados_guardados
		visitados_auxiliar_crear_obtener_lista
		recordar_posicion()
		limpiar_lista_de_visitados()
		activar_guardar_visitados()
		desactivar_guardar_visitados()
		Todas las pruebas superadas!!
		modificado destruir_datos_temporales() pruebas superadas
V2.0.2.200522
		modificaciones internas
	Nota: no se puede guardar la posicion actual en ninguna funcion de la interfa de carga y creaci�n y destrucci�n ya q es imposible que se haya activado la bandera de guardar recorrido cuando todavia no existia el iterador.
		_actual() modificado, guarda bien
		avanzar y _avanzar pruebas superadas
V2.0.3.200522
		inicio() y ultimo() pruebas superadas
***************************************************************************************************
V2.1.0.250826 Quito los numeros de las versiones. Agiorno a PHP 8


*/
include_once("../nucleo/Nodo.php");

class Iterador extends Objeto{
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//----------------------	CLASE ITERADOR	------------------------------------ >
	//------------------------------------------------------------------------------->
	
	/*	NOTAS DE CLASE:
		
			SOBRE LA CREACION DE ITERADORES
				Se agregan tres funciones estaticas internas para centralizar la creacion de los iteradores.

			SOBRE LA ESTRUCTURA INTERNA DE LA CLASE (INFORMACION SENSIBLE)
				Se tendra la informacion a partir del nodo con id especial "iteradores". A partir de el se asignara
				los nodos de clase, y de estos los cuerpos de cada iterador. Ver "Estructura iteradores 2019.png"
			SOBRE LA CREACION Y VALIDACION DE NODOS
				Para insertar nuevos nodos a la clase siempre es conveniente crearlos en la misma clase para asegurar la compativilidad entre el iterador y la estructura de nodos que se va generano.
				Para lograr que cada clase heredera a iterador puedo desarrollar sus propias reglas se dise�o la clase de tal manera que los procesos que crean nuevos nodos o tienen que validar un nodo pasado como parametro puedan, puedan acceder a una unica forma de crear y validar los nodos. Esa unica forma es el proceso nodo(elemento, es_nodo) que en su interior utiliza a la funcion es_nodo_valido. bastara con redefinir esta ultima funcion en las clases herederas para generar nuevas reglas. Tambien se podria conisederar redefinir el proceso nodo si se considerara necesario en algun caso ahora inimaginable.
			
			FUNCIONES DE INSERCION Y CREACION DE NODOS
			
				*nodo($elemento=null, &$es_nodo=null): 
				*crear($nombre, $elemento=null, &$es_nodo=null)
				*cargar($nombre, $elemento=null, &$es_nodo=null)
				*iterador($nombre, $elemento=null, &$es_nodo=null, &$nuevo=null)
				*_actual($elemento=null,&$es_nodo=null)
				*_avanzar($alias, $elemento=null, $camino=null, &$es_nodo=null)
				*_adyacente_en($elemento, $alias, $camino=null, &$es_nodo=null)
				*_adyacentes($alias, $elemento=null, $camino=null, &$es_nodo=null)
				*_como_adyacente_de_nodo_en_alias($elemento, $alias, $camino=null, &$es_nodo=null)
				*_adyacente_inverso($alias, $elemento=null, $camino=null, &$es_nodo=null)

	

			
	*/
		//el nodo raiz del cuerpo del iterador
		protected $raiz_cuerpo;
	
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de creaci�n de nodos y validacion de elementos >
	//------------------------------------------------------------------------------->

			
			/*FUNCION es_elemento_valido($elemento, &$es_nodo=null)--------------------------------------------->
				Interfaz: Interfaz de creaci�n de nodos y validacion de elementos
			+--------------------------------------------
				Caso de uso: verifica si un elemento o nodo es valido
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					&$elemento: el elemento que se desea verificar
					&$es_nodo: (opcional) devuelve true si el elemento pasado como parametro era un nodo, false en caso contrario.
			+--------------------------------------------
				Notas: Iterador permite todo tipo de elementos*, en el caso de que el elemento ingresado sea 0 o null devuelve true.
						*ESTA FUNCION DEBERA SER MODIFICADA EN LAS CLASES HEREDERAS PARA CAMBIAR LAS CONDICIONES QUE DEBEN CUMPLIR LOS ELEMENTOS PARA SER VALIDOS PARA EL ITERADOR.
			+--------------------------------------------
				Cuerpo:
			*/
			static public function es_elemento_valido($elemento, &$es_nodo=null){
				/*$dato;
				//echo "AA";
				if ($elemento instanceof Nodo){
					//echo "TTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTTT";
					$es_nodo=true;
					//$elemento->dato();
					//echo "ES NODO VALIDO: ".$dato."\n";
				}else{
					//echo "RRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR";
					//$dato=$elemento;
					$es_nodo=false;
				}
				//if ($dato==false){
					return true;
			//	}
				//return $dato;*/
				$es_nodo=$elemento instanceof Nodo;
				return true;

			}
			/*-------------------------------------------
				Datos de salida: Iterador permite todo tipo de elementos por lo que devuelve el dato que contenia el elemento, en el caso de que el elemento ingresado sea 0 o null tambien devuelve true.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de es_elemento_valido($elemento, &$es_nodo=null)*/

		/*FUNCION nodo($elemento=null, &$es_nodo)------------------------------------------------------>
				Interfaz: Interfaz de creaci�n de nodos y validacion de elementos
			+--------------------------------------------
				Caso de uso: obtener nodo a partir de elemento o nodo
			+--------------------------------------------
				Precondiciones: que el elemento, contenido o no en un nodo, pasado por parametro cumpla las condiciones de la clase de iterador para ser un elemento valido
			+--------------------------------------------
				Datos de entrada:
					$elemento: (opcional) el elemento o nodo del que se desea obtener un nodo. Si no se utiliza esta variable o se le pasa null se obtendra un nodo "vacio".
					&$es_nodo: (opcional) devuelve true si el elemento pasado como parametro era un nodo, false en caso contrario.
			+--------------------------------------------
				Notas: Esta funcion tiene en su interior una llamada a la funcion es_elemento_valido. En la clase Iterador esa funcion permite cualquier tipo de elemento. Por lo tanto si se desea cambiar el comportamiento de nodo para que tenga otras restricciones se debera modificar la funcion es_elemento_valido de la clase heredera.
			+--------------------------------------------
				Cuerpo:
			*/
			public function nodo($elemento=null, &$es_nodo=null){
				if (!$this->es_elemento_valido($elemento, $es_nodo)){
					Iterador::_error("Iterador::nodo(elemento=null, &es_nodo=null) el elemento no es valido");
					return null;						
				}
				if ($es_nodo){
					return $elemento;
				}else{
					return Nodo::crear_con_dato($elemento);
				}
			}
			/*-------------------------------------------
				Datos de salida: el nodo con el elemento si es que ya no era un nodo. o el nodo directamente si ya era un nodo el elemento. En la variable &$es_nodo se devuelve true  o false dependiendo si el elemento era o no un nodo. Devuelve null en el caso de que el elemento no haya superado la prueba de la funcion es_elemento_valido.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de nodo($elemento=null, &$es_nodo=null)*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de carga y creaci�n y destrucci�n-------------->
	//------------------------------------------------------------------------------->

		/*FUNCION __construct()------------------------------------------------------>
				Interfaz: Carga, creacion y destruccion
			+--------------------------------------------
				Caso de uso: crea interno
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
				*/
			private function __construct(){

            }
			/*-------------------------------------------
				Datos de salida: un iterador sin construir
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de __construct()*/

		//*****************************************************************************>
		//-------------Interfaz Interna de carga, creacion y destruccion interna ------>
		//----------------------------------------------------------------------------->
		//----------------------------------------------------------------------------->
			

		/*FUNCION STATIC PROTECTED registrar_clase($iterador)-------------------------->
				Interfaz: Interna de carga, creacion y destruccion interna
				Caso de uso: registrar clase
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$iterador: el iterador que del cual queremos registrar su clase
			+--------------------------------------------
				Notas: (V1.9.3) Los iteradores deben permitir ser cargados solamente desde la misma clase que fue creado. Para solucionarlo vamos a guardar en el nodo "raiz_cuerpo" del iterador un enlace a un nodo que contenga el nombre de la clase.
				El nodo que contiene el nombre de la clase no es un nodo distinto para cada iterador sino que es uno distinto por cada clase. (desechado en 1.9.4: Los mismos estaran enlazados desde un nodo con id especial "clases de iteradores". El nombre de la clase estara tanto en el nombre del enlace como en el dato del nodo enlazado.) A partir de 1.9.4 existe un solo nodo con "id especial", "iteradores", el mismo tendra como enlace a los nombres de cada una de las clases de iterador registrada. Estos nodos enlasados seran el centro de la informacion de cada clase como se idica en "estructura iteradores 2019.png". Cada nodo centro contendra al menos dos enlaces: 1) "iteradores" que apuntara a un nodo desde donde se enlacen cada cuerpo de iterador; 2) "informacion compartida" que apuntara a un nodo que contiene el nombre de la clase. Este ultimo es a su vez apuntado por un enlace ("clase") en cada raiz de iterador registrada de modo que cada iterador de una clase pueda tener acceso a la informacion compartida de esa clase como por ej los "alias permitidos". Ver "estructura iteradores 2019.png" para entender mejor.
						
			+--------------------------------------------
				Cuerpo: */
				static protected function registrar_iterador($iterador, $nombrei){
					//ver si el iterador pertenece a la clase o es heredera de Iterador
					
				//	echo "entro aca".$nombrei;
					if (!($iterador instanceof Iterador)){
						Iterador::_error("Iterador::registrar_iterador(iterador) el dato de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
						return null;
					}
					if ($iterador->raiz_cuerpo){
						Iterador::_error("Iterador::registrar_iterador(iterador) el Iterador pasado por parametro ya fue creado antes");
						return null;
					}
					
					//ver si existe el nodo con id especial "iteradores"
					If (!$nclases=Nodo::nodo_por_id("iteradores")){
						$nclases=Nodo::crear_con_id("iteradores");
					}
					
					//obtener el nombre de la clase
					$nombrec=get_class($iterador);
					//ver si el nombre esta registrado
					$nclase=null;
					if (!$nclase=$nclases->adyacente($nombrec)){
						//registrar
						$nclase=Nodo::crear();
						$nclases->_adyacente_en($nclase,$nombrec);
					}
					
					//verificar si existe "enlaces permitidos" sino integrarlo
					if (!$nclase->adyacente("alias permitidos")){
						if ((!$npermitidos=$iterador->_alias_permitidos()) or (!($npermitidos instanceof Nodo))){
							Iterador::_error("Iterador::registrar_iterador(iterador) error asignando los enlaces permitidos de ".$nombrec);
							return null;					
						}else{
							$nclase->_adyacente_en($npermitidos, "alias permitidos");
						}
					}

					//obtener o crear el nodo "informacion compartida"

					$ninformacion=null;
					if (!$ninformacion=$nclase->adyacente("informacion compartida")){
						$nclase->_adyacente_en($ninformacion=Nodo::crear_con_dato($nombrec), "informacion compartida");
					}
					//integrar nodo de "enlaces permitidos"
				/*	if (!$npermitidos=$iterador->_alias_permitidos()){
						Iterador::_error("Iterador::registrar_iterador(iterador) ya existe un iterador con ese nombre");
						return null;					
					};*/
					
					//obtengo o creo el nodo q apunta a todos los iteradores
					$niteradores=null;
					if (!$niteradores=$nclase->adyacente("iteradores")){
						$nclase->_adyacente_en($niteradores=Nodo::crear(), "iteradores");
					}
					//verifico si no existe un iterador con ese nombre
					if ($niteradores->adyacente($nombrei)){
						Iterador::_error("Iterador::registrar_iterador(iterador) ya existe un iterador con ese nombre");
						return null;
					}
					//

					$niteradores->_adyacente_en($cuerpoi=Nodo::crear_con_dato($nombrei),$nombrei);
					
					$iterador->raiz_cuerpo=$cuerpoi;
					$cuerpoi->_adyacente_en($ninformacion, "clase");

					return $cuerpoi;
				}
			/*-------------------------------------------
				Datos de salida: el "nodo clase" en el caso de exito, flase en caso contrario
			+--------------------------------------------
				Poscondiciones: quedo la clase registrada en el caso de que ya no hubiera sido registrada antes.
			+--------------------------------------------
		
		<-----------------------Fin de registrar_clase(iterador)*/
			

		/*FUNCION STATIC PROTECTED crear_interno($nombre, $iterador, $elemento=null, &$es_nodo=null)---------------->
				Interfaz: Interna de carga, creacion y destruccion interna
				Caso de uso: crea iterador interno
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre con el cual se desea bautizar al iterador
					$iterador: una instancia nueva de iterador o heredero(sin raiz de cuerpo)
					$elemento (opcional): un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera crear el nodo con esa condicion ANTES y pasarlo en este parametro.
					&$es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Notas: (V1.9.3) Este proceso esta destinado a ser utilizado por los crear de cada una de las clases herederas y por la propia clase Iterador
			+--------------------------------------------
				Cuerpo: */	
				static protected function crear_interno($nombre, &$iterador, $elemento=null, &$es_nodo=null){
					//verifico datos de entrada
					if (!is_string($nombre)){
						Iterador::_error("Iterador::crear_interno(nombre, iterador, elemento=null, &es_nodo=null) de la clase Iterador, el nombre del iterador debe ser un string");
						return null;
					};
					//registro la clase

					if (!$cuerpo=Iterador::registrar_iterador($iterador, $nombre)){
						Iterador::_error("Iterador::crear_interno(nombre, iterador, elemento=null, &es_nodo=null) la clase del iterador no es valida");
						return null;					
					}

					//lo asigna como ocuapado
					$cuerpo->_adyacente_en($cuerpo, "ocupado");
					//asigno el actual en el caso de que sea valido
					//verifico que el elemento de entrada sea valido
					$nodo=null;
					if ($elemento){
						if (!$nodo=$iterador->nodo($elemento, $es_nodo)){
							Iterador::_error("Iterador::crear_interno(nombre, iterador, iterador, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de ".$nombre." no es valido");
							Iterador::destruir_interno($iterador);
							return null;	
						}else{

							$cuerpo->_adyacente_en($nodo, "actual");
						}
					}
					//retorna el iterador
					return $iterador;
				}
			/*-------------------------------------------
				Datos de salida: un iterador con cuerpo y registradoSi estaba presente $elemento dicho elemento quedara apuntado como la posicon "actual". $es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Poscondiciones: el iterador y la clase quedan registrados
			+--------------------------------------------
		<-----------------------Fin de crear_interno($nombre, $iterador, $elemento=null, &$es_nodo=null)*/

		/*FUNCION destruir_interno($iterador)------------------------------------------------------>
				Interfaz: Carga, creacion y destruccion
			+--------------------------------------------
				Caso de uso: destruye interno
			+--------------------------------------------
				Precondiciones: que hayan sido eliminados todos los enlaces agregados al nodo cuerpo del iterador en las clases herederas
			+--------------------------------------------
				Datos de entrada: 
					$iterador: el iterador a destruir
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:*/
			static protected function destruir_interno($iterador){
				//$cuerpo=$iterador->raiz_cuerpo;
				if ((!$cuerpo=$iterador->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::destruir_interno(iterador) el iterador no esta ocupado");
					return false;
				}

				//$this->liberar();
				
				$iterador->destruir_datos();
				$iterador->destruir_datos_individuales();
				$iterador->destruir_datos_temporales();
							//echo "333333333333333333333333333333333333333333333333333333333333333333333333333";
			//	$this->raiz_cuerpo->imprimir();

				if ($nclones=$cuerpo->adyacente("cantidad de clones")){
					//echo "333333333333333333333333333333333333333333333333333333333333333333333333333";
					$cuerpo->eliminar_enlace("cantidad de clones");
					Nodo::eliminar($nclones);
				}
				$cuerpo->eliminar_enlace("clon");

				$iterador->eliminar_todos_los_alias();
				
				$cuerpo->eliminar_enlace("ocupado");
				//$iterador->desocupar();
				$its=Nodo::nodo_por_id("iteradores");
				
				$clase=get_class($iterador);
				$nclase=$its->adyacente($clase);
				$niteradores=$nclase->adyacente("iteradores");
				$niteradores->eliminar_enlace($cuerpo->dato());
				//echo "GOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOLLLLLLLLLLLLL";
				if (!Nodo::eliminar($cuerpo)){
					Iterador::_error("Iterador::destruir_interno(iterador) no se pudo destruir el cuerpo del iterador, asegurese de haber destruido todos los enlaces que apunten al cuerpo del iterador antes de llamar a esta funcion");
					return false;		
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true si tuvo exito, false, en caso contrario
			+--------------------------------------------
				Poscondiciones: el cuerpo del iterador fue elminado junto con el enlace que apuntaba a el en el registro de iteradores.
			+--------------------------------------------
		<-----------------------Fin de destruir_interno($iterador)*/


		/*FUNCION STATIC PROTECTED cargar_interno($nombre, $iterador, $elemento=null, &$es_nodo=null)--------------->
				Interfaz: Interna de carga, creacion y destruccion interna
				Caso de uso: carga iterador interno
			+--------------------------------------------
				Precondiciones: que haya sido creado con anterioridad un iterador con el nombre proporcionado y que el mismo no este ocupado
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre del iterador que se decea cargar
					$iterador: una instancia nueva de iterador o heredero(sin raiz de cuerpo)
					$elemento (opcional): 	un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera encapsularlo en un nodo ANTES de pasarlo a esta funcion.  si ya existia un elemento en la posicion actual emitira una alerta, pero igual asignara el nuevo elemento como actual.
					&$es_nodo (opcional): en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.

			+--------------------------------------------
				Notas: (V1.9.3) Este proceso esta destinado a ser utilizado por los cargar de cada una de las clases herederas y por la propia clase Iterador
			+--------------------------------------------
				Cuerpo: */	
				static protected function cargar_interno($nombre, $iterador, $elemento=null, &$es_nodo=null){
					//comprobar datos de entrada
					if (!is_string($nombre)){
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el nombre del iterador debe ser un string");
						return null;
					};
					if (!($iterador instanceof Iterador)){
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
						return false;
					}
					if ($iterador->raiz_cuerpo){
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador pasado por parametro ya fue creado antes");
						return null;
					}
					//recuperar nodo iteradores
					if(!$iteradores=Nodo::nodo_por_id("iteradores")){
						//$iteradores->imprimir();
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existen iteradores");
						return null;
					}
					//recupero el nodo clase
					$nombrec=get_class($iterador);
					if(!$nclase=$iteradores->adyacente($nombrec)){
						//$iteradores->imprimir();
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existen iteradores de esa clase");
						return null;
					}
					//recupero el nodo iteradores
					if(!$nits=$nclase->adyacente("iteradores")){
						//$iteradores->imprimir();
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) error interno en la estructura");
						return null;
					}
					//recupero el cuerpo
					if (!$cuerpo=$nits->adyacente($nombre)){
						//$iteradores->imprimir();
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) no existe ningun iterador con el nombre ".$nombre."...");
						return null;
					};

					//comprobar ocupado
					if ($cuerpo->adyacente("ocupado")){
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador que intenta cargar esta ocupado");
						return null;
					}
					//comprobar clases
					if ((!$clase=$cuerpo->adyacente("clase"))or ($clase->dato()!=get_class($iterador))){
						Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador que se intenta cargar no pertenece a esta clase");
						return null;
					};
								
					//asignar cuerpo
					$iterador->raiz_cuerpo=$cuerpo;
					//retornar iterador
					$cuerpo->_adyacente_en($cuerpo, "ocupado");
					//verifico que el elemento de entrada sea valido
					if ($elemento){
						$nodo=null;
						if (!$nodo=$iterador->nodo($elemento,$es_nodo)){
							Iterador::_error("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de ".$nombre." no es valido");
							$cuerpo->eliminar_enlace("ocupado");
							return null;	
						}else{
							if ($cuerpo->adyacente("actual")){
								Iterador::_alerta("Iterador::cargar_interno(nombre, iterador, elemento=null, &es_nodo=null) el iterador ya tenia una posicion actual, de todas formas se asignara la nueva pasada por parametro");
							}
							$cuerpo->_adyacente_en($nodo, "actual");
						}
					}

					return $iterador;
				}
			/*-------------------------------------------
				Datos de salida: un iterador con cuerpo. Si estaba presente $elemento dicho elemento quedara apuntado como la posicon "actual". $es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.

			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de cargar_interno($nombre, $iterador, $elemento=null, &$es_nodo=null)*/
		
		/*FUNCION STATIC PROTECTED iterador_interno($nombre, $iterador, &$nuevo=null, $elemento=null, &$es_nodo=null, &$nuevo=null) ------------------------->
				Interfaz: Interna de carga, creacion y destruccion interna
				Caso de uso: carga o crea iterador interno
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre del iterador que se decea cargar o crear
					$iterador: una instancia nueva de iterador o heredero(sin raiz de cuerpo)
					$elemento (opcional): un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera encapsularlo en un nodo ANTES de pasarlo a esta funcion.  si ya existia un elemento en la posicion actual emitira una alerta, pero igual asignara el nuevo elemento como actual.
					&$nuevo: en esta variable devuelve true si se creo un iterador nuevo, false en caso contrario 
					&$es_nodo (opcional): en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Notas: (V1.9.3) Este proceso esta destinado a ser utilizado por los iterador() de cada una de las clases herederas y por la propia clase Iterador
			+--------------------------------------------
				Cuerpo: */	
				static protected function iterador_interno($nombre, $iterador, &$nuevo=null, $elemento=null, &$es_nodo=null){
					//echo "entro aca2".$nombre;
					//$nombre=$nombre."oaoa";
					//comprobar datos de entrada
					if (!is_string($nombre)){
						Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el nombre del iterador debe ser un string");
						return null;
					};
					if (!($iterador instanceof Iterador)){
						Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
						return false;
					}
					if ($iterador->raiz_cuerpo){
						Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador pasado por parametro ya fue creado antes");
						return null;
					}
					//recupero el nodo clase
					$nombrec=get_class($iterador);
					
					if (($iteradores=Nodo::nodo_por_id("iteradores")) and ($nclase=$iteradores->adyacente($nombrec))and ($nits=$nclase->adyacente("iteradores"))and  ($cuerpo=$nits->adyacente($nombre))){
						//$nombre=$nombre."oaoa1";
						//comprobar ocupado
						
						if ($cuerpo->adyacente("ocupado")){
							Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador que intenta cargar esta ocupado");
							return null;
						}
						//comprobar clases
						if ((!$clase=$cuerpo->adyacente("clase"))or ($clase->dato()!=get_class($iterador))){
							Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador que se intenta cargar no pertenece a esta clase");
							return null;
						};
									
						//asignar cuerpo
						$iterador->raiz_cuerpo=$cuerpo;
						//retornar iterador
						$cuerpo->_adyacente_en($cuerpo, "ocupado");
						//verifico que el elemento de entrada sea valido
						if ($elemento){
							$nodo=null;
							if (!$nodo=$iterador->nodo($elemento,$es_nodo)){
								Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el elemento que intenta asignar con la carga de ".$nombre." no es valido");
								$cuerpo->eliminar_enlace("ocupado");
								return null;	
							}else{
								if ($cuerpo->adyacente("actual")){
									Iterador::_alerta("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) el iterador ya tenia una posicion actual, de todas formas se asignara la nueva pasada por parametro");
								}
								$cuerpo->_adyacente_en($nodo, "actual");
							}
						}
						$nuevo=false;
						return $iterador;

					}else{
						
						if (!$cuerpo=Iterador::registrar_iterador($iterador, $nombre)){
							Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, elemento=null, &es_nodo=null) la clase del iterador no es valida");
							return null;					
						}
						
						//lo asigna como ocuapado
						$cuerpo->_adyacente_en($cuerpo, "ocupado");
						//asigno el actual en el caso de que sea valido
						//verifico que el elemento de entrada sea valido
						$nodo=null;
						if ($elemento){
							if (!$nodo=$iterador->nodo($elemento, $es_nodo)){
								Iterador::_error("Iterador::iterador_interno(nombre, iterador, &nuevo=null, iterador, elemento=null, &es_nodo=null) el elemento que intenta asignar con la creacion de ".$nombre." no es valido");
								Iterador::destruir_interno($iterador);
								return null;	
							}else{
								$cuerpo->_adyacente_en($nodo, "actual");
							}
						}
						//retorna el iterador
						$nuevo=true;
						return $iterador;
					}
				}
			/*-------------------------------------------
				Datos de salida: un iterador con el nombre proporcionado. Si elemento no es nulo, el mismo pasara a estar apuntado por la posicion actual del iterador, si el elemento no era un nodo, la funcion lo encapsula en un nodo; la variable pasada por referencia es_nodo devuelve si el elemento era o no un nodo. la variable pasada por referencia $nuevo reflejara si se creo un Iterador nuevo o se cargo uno que ya existia desde antes.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de iterador_interno($nombre, $iterador, &$nuevo=null, $elemento=null, &$es_nodo=null)*/

		/*FUNCION crear(nombre, elemento=null, &es_nodo=null)------------------------------------------------------>
				Interfaz: Carga, creacion y destruccion
				Caso de uso: crea iterador con nombre
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre del iterador que se esta creando
					$elemento (opcional): un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera crear el nodo ANTES de pasarlo a esta funcion.
					&$es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Notas: (V1.9.3) Los iteradores deben permitir ser cargado solamente desde la misma clase que fue creado. Para solucionarlo vamos a guardar en el nodo "raiz_cuerpo" del iterador un enlase a un nodo que contenga el nombre de la clase
			+--------------------------------------------
				Cuerpo: 			*/

				static public function crear($nombre, $elemento=null, &$es_nodo=null){
					$iter= new Iterador;
					if (!Iterador::crear_interno($nombre, $iter, $elemento, $es_nodo)){
						Iterador::_error("Iterador::crear(nombre, elemento, &es_nodo) no se pudo crear");
						return null;
					}
					return $iter;
				}
			/*-------------------------------------------
				Datos de salida: un iterador con el nombre y elemento proporcionado.Si estaba presente $elemento dicho elemento quedara apuntado como la posicon "actual". $es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de crear(nombre, elemento=null, &es_nodo=null)*/
		/*FUNCION destruir()------------------------------------------------------>
				Interfaz: Carga, creacion y destruccion
				Caso de uso: destruir el iterador
			+--------------------------------------------
				Precondiciones: que cualquier enlace agregado al nodo-cuerpo del iterador por clases herederas haya sido elimnado
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function destruir(){
				if (!Iterador::destruir_interno($this)){
					Iterador::_error("Iterador->destruir() no se completo el proceso de destruiccion");
					return false;
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true en el caso de exito, false en el caso contrario
			+--------------------------------------------
				Poscondiciones: el iterador fue eliminado por completo
			+--------------------------------------------
		<-----------------------Fin de iterador(nombre)*/


		/*FUNCION cargar($nombre, $elemento=null, &$es_nodo=null)------------------------------------------------>
				Interfaz: Carga, creacion y destruccion
				Caso de uso: carga iterador con nombre
			+--------------------------------------------
				Precondiciones: que se haya creado anteriormente un iterador con ese nombre y que el mismo no este ocupado
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre del iterador que se desea cargar
					$elemento (opcional): un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera encapsularlo en un nodo ANTES de pasarlo a esta funcion.
					&$es_nodo (opcional): en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Notas: 1.9.3
			+--------------------------------------------
				Cuerpo: 			*/

			static public function cargar($nombre, $elemento=null, &$es_nodo=null){
				$iter= new Iterador;
				if (!Iterador::cargar_interno($nombre, $iter, $elemento, $es_nodo)){
					Iterador::_error("Iterador::cargar(nombre, elemento=null, &es_nodo=null) no se pudo cargar");
					return null;
				}
				return $iter;
			}
			/*-------------------------------------------
				Datos de salida: 
					un iterador con el nombre proporcionado. Si estaba presente $elemento dicho elemento quedara apuntado como la posicon "actual". $es_nodo: en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de cargar($nombre, $elemento=null, &$es_nodo=null)*/
		
		/*FUNCION iterador($nombre, &$nuevo=null, $elemento=null, &$es_nodo=null) --------------------------------->
				Interfaz: Carga, creacion y destruccion
				Caso de uso: carga o crea un iterador con nombre
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$nombre: el nombre del iterador que se decea cargar o crear
					$iterador: una instancia nueva de iterador o heredero(sin raiz de cuerpo)
					$elemento (opcional): un elemento que pasara a estar en la posicion actual del Iterador. Si el elemento no es un nodo, la funcion lo encapsula en un nodo. Este elemento debe pasar la prueba de validacion es_elemento_valido de la clase. Si el elemento es null no sera tenido en cuenta. Si quiere asignar un elemento null o "vacio" a la posicion actual debera encapsularlo en un nodo ANTES de pasarlo a esta funcion.  si ya existia un elemento en la posicion actual emitira una alerta, pero igual asignara el nuevo elemento como actual.
					&$nuevo (opcional): en esta variable devuelve true si se creo un iterador nuevo, false en caso contrario 					
					&$es_nodo (opcional): en esta variable opcional pasada como referencia se almacenara true si el elemento pasado como parametro (en el caso de existir) sea un nodo; o false en el caso de que el elemento haya tenido que ser encapsulado en un nodo por la propia funcion.
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			static public function iterador($nombre,  $elemento=null, &$es_nodo=null, &$nuevo=null){
				$iter= new Iterador;
				if (!Iterador::iterador_interno($nombre, $iter, $nuevo, $elemento, $es_nodo)){
					Iterador::_error("Iterador::iterador(nombre, &nuevo=null, elemento=null, &es_nodo=null, &nuevo=null) no se pudo cargar ni crear el iterador con ese nombre");
					return null;
				}
				return $iter;
			}	
			/*-------------------------------------------
				Datos de salida: un iterador con el nombre proporcionado. Si elemento no es nulo, el mismo pasara a estar apuntado por la posicion actual del iterador, si el elemento no era un nodo, la funcion lo encapsula en un nodo; la variable pasada por referencia es_nodo devuelve si el elemento era o no un nodo. la variable pasada por referencia $nuevo reflejara si se creo un Iterador nuevo o se cargo uno que ya existia desde antes.
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de iterador($nombre, &nuevo=null, $elemento=null, &$es_nodo=null, &$nuevo=null)*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ de Propiedades del Iterador ------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->


		/*FUNCION es_iterador(iterador)------------------------------------------------------>
				Interfaz: Propiedades del Iterador 
				Caso de uso: verificar si es un iterador
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$elemento: el elemento que se desea saber si es un iterador o no
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			static public function es_iterador($elemento){
				$es=false;
				if ($elemento instanceof Iterador){
					$es=true;
				}
				return $es;
			}
			/*-------------------------------------------
				Datos de salida: true si el elemento es un iterador, false en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de es_iterador(nombre)*/

		/*FUNCION nombre()------------------------------------------------------>
				Interfaz: Propiedades del Iterador
				Caso de uso: obtener nombre del iterador
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Incorporada en version: V1.7.171101
			+--------------------------------------------
				Cuerpo:
			*/
			public function nombre(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->nombre() el iterador no esta ocupado");
					return false;
				}
				return $cuerpo->dato();
			}
			/*-------------------------------------------
				Datos de salida: el nombre del iterador. Null en el caso de error
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de nombre()*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ de Marca de ocupado---------------------------->
	//------------------------------------------------------------------------------->
	//--V1.8------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			Esta interfaz tiene como tarea administrar el axeso a la "marca de ocupado" del iterador.
			Esta marca en la relidad no es mas que un elace de la raiz del iterador a si misma.
		*/


		/*FUNCION ocupar()------------------------------------------------------>
				Interfaz: INTERFAZ OPCUPAR/DESOCUPAR/OCUPADO
			+--------------------------------------------
				Caso de uso: Activar la "marca de ocupado"
			+--------------------------------------------
				Precondiciones: Que el iterador no este ocupado
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			protected function ocupar(){
				if (!$cuerpo=$this->raiz_cuerpo){
					$this->_error("Iterador->ocupar() el iterador no tiene cuerpo!!");
					return false;
				}
				if (!$cuerpo->adyacente("ocupado")){
					$cuerpo->_adyacente_en($cuerpo,"ocupado");
					return true;
				}else{
					$this->_alerta("Iterador->ocupar() el iterador ya esta ocupado");
					return false;		
				}
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el iterador queda con una "marca de ocupado"
			+--------------------------------------------
		<-----------------------Fin de ocupar()*/
		
		/*FUNCION desocupar()------------------------------------------------------------------>
				Interfaz: INTERFAZ OPCUPAR/DESOCUPAR/OCUPADO
			+--------------------------------------------
				Precondiciones: Que el iterador este ocupado
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function desocupar(){
				//echo "<br>HHHHHHHHHHHHHHHH";
				if (!$cuerpo=$this->raiz_cuerpo){
					$this->_alerta("Iterador->desocupar() el iterador ya esta desocupado(1)");
					return false;
				}
				if ($cuerpo->adyacente("ocupado")){
					//$this->liberar(); //eliinado en 1.9.0
					//	echo "HHHHHHHHHHHHHHHH1";
					$this->destruir_datos_temporales();
					$cuerpo->eliminar_enlace("ocupado");			
						//echo "HHHHHHHHHHHHHHHH2";
					$this->raiz_cuerpo=null;
					return true;
				}else{
						//echo "HHHHHHHHHHHHHHHH3";
					$this->_alerta("Iterador->desocupar() el iterador ya esta desocupado (2)");
					return false;		
				}	
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: se elimino la "marca de ocupado" del iterador
			+--------------------------------------------
		<-----------------------Fin de deocupar()*/
		
		/*FUNCION ocupado()------------------------------------------------------------------>
				Interfaz: INTERFAZ OPCUPAR/DESOCUPAR/OCUPADO
			+--------------------------------------------
				Precondiciones: Que el iterador este ocupado
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function ocupado(){
				if (($cuerpo=$this->raiz_cuerpo)and($cuerpo->adyacente("ocupado"))){
					return true;
				}else{
					return false;		
				}	
			}
			/*-------------------------------------------
				Datos de salida: true si existe la "marca de ocupado", false en caso contrario. 
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de ocupado()*/

	//..................................................................
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//----------------------manejo de ALIAS------------------------------------------>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
		*/

		//private $alias;
		/*FUNCION protected _alias_permitidos()------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: asigna alias validos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					
			+--------------------------------------------
				Notas: Esta funcion debe ser redefinida en cada clase de iterador q necesite alias especificos para funcionar. La misma debe devolver un nodo con enlaces a si mismo cuyos nombre de enlace sean los nombres de los alias permitidos
			+--------------------------------------------
				Cuerpo:
				*/
			static protected function _alias_permitidos(){
				return Nodo::nodo();
			}
			/*-------------------------------------------
				Datos de salida: un nodo con los nombre de los alias permitidos como enlaces a si mismo
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de _alias_permitidos()*/

		/*FUNCION protected es_alias_valido($alias)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: verificar alias valido
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias que se desea verificar si es valido
					$iterador: el iterador sobre el cual se realiza la pregunta
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
				*/
			static protected function es_alias_valido(&$alias, $iterador){
				if (!is_string($alias)){
					Iterador::_error("Iterador::es_alias_valido(alias) el alias que se intenta validar no es un string");
					return false;	
				};
				$alias=trim($alias);
				if (strlen($alias)<1){
					Iterador::_error("Iterador::es_alias_valido(alias) el alias que se intenta validar no puede ser un string vac�o");
					return false;
				};
				/*if (((int)$alias)!=0){
					Iterador::_error("el alias que intenta validar es la representacion de un entero");
					return false;
				};*/

				if ($alias=="0"){
					Iterador::_error("Iterador::es_alias_valido(alias) el alias que intenta validar es la representacion de un entero");
					return false;
				}
				$largo=strlen($alias);
				//echo $largo;
				//ver si existe el nodo con id especial "iteradores"
				If (!$nclases=Nodo::nodo_por_id("iteradores")){
					$nclases=Nodo::crear_con_id("iteradores");
				}
				//obtener el nombre de la clase
				$nombrec=get_class($iterador);
				//ver si el nombre esta registrado
				$nclase=null;
				if (!$nclase=$nclases->adyacente($nombrec)){
					//registrar
					$nclase=Nodo::crear();
					$nclases->_adyacente_en($nclase,$nombrec);
				}
					
				//verificar si existe "enlaces permitidos" sino integrarlo
				$npermitidos=$nclase->adyacente("alias permitidos");
				
				if ($npermitidos->tiene_adyacente()){
					if (!$npermitidos->adyacente($alias)){
						Iterador::_error("Iterador::es_alias_valido(alias) el alias que intenta validar no esta permitido en esta clase");
						return false;
					}
				}
				//eliminado en V1.7.170304
		/*		for ($i=0; $i<$largo; $i++){
					//echo "*".$alias[$i]."*";
					if ($alias{$i}===' '){
						Iterador::_error("Iterador::es_alias_valido(alias) el alias que intenta validar contiene un caracter vacio");
						return false;
					}
				}*/
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true si el alias pasado como parametro es valido, false en caso contrario
			+--------------------------------------------
				Poscondiciones: si el alias pasado como parametro tenia espacios en blanco al principio o al final los mismos son eliminados
			+--------------------------------------------
		<-----------------------Fin de es_alias_valido(alias)*/
	
		/*FUNCION protected _alias($enlace, $alias)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: verificar alias valido
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$enlace: el nombre del enlace al que se le desea asignar un alias
					$alias: el alias que se desea verificar si es valido
			+--------------------------------------------
				Notas:
					esta es la unica funcion de asignar un alias
					los espacios en blanco al principio y al final de enlace en el caso de se un tring y alias son eliminados
			+--------------------------------------------
				Cuerpo:
				*/
			public function _alias($enlace, $alias){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::_alias(enlace, alias) el iterador no esta ocupado");
					return false;
				}	
				//verifico tipos de datos de entrada
				$es=null;
				if ((!is_int($enlace))and(!$es=is_string($enlace))){
					$this->_error("Iterador::_alias(enlase, alias) el enlace al que se le intenta asignar un alias no es un entero ni un string");
					return false;
				};
				if ($es){
					$enlace=trim($enlace);
				}
				if (!Iterador::es_alias_valido($alias, $this)){
					$this->_error("Iterador::_alias(enlase, alias) el alias que intenta asignar no cumple con el formato requerido");
					return false;
				}
				
				//inicializacion peresoza del nodo "alias"
				if (!$nalias=$cuerpo->adyacente("alias")){
					$cuerpo->_adyacente_en($nalias=Nodo::crear(),"alias");
				}
				//inicializacion peresoza del nodo "enlaces alias"
				if (!$nenlacesalias=$cuerpo->adyacente("enlaces alias")){
					$cuerpo->_adyacente_en($nenlacesalias=Nodo::crear(),"enlaces alias");
				}
				$datoant=null;
				if ($ant=$nalias->adyacente($alias)){
					$datoant=$ant->dato();
					$ant->_dato($enlace);
					$nodoeli=$nenlacesalias->eliminar_enlace($datoant);
					Nodo::eliminar($nodoeli);
				}else{
					$nalias->_adyacente_en(Nodo::crear_con_dato($enlace),$alias);
				}
				if ($ant=$nenlacesalias->adyacente($enlace)){
					$datoant=$ant->dato();
					$ant->_dato($alias);
					$nodoeli=$nalias->eliminar_enlace($datoant);
					Nodo::eliminar($nodoeli);
				}else{
					$nenlacesalias->_adyacente_en(Nodo::crear_con_dato($alias),$enlace);
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: fueron eliminados los espacios en blanco al principio y final de $enlace si era un string y de $alias
			+--------------------------------------------
		<-----------------------Fin de _alias($enlace, $alias)*/
		
		/*FUNCION protected eliminar_alias($alias)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: elimina un alias
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias que se desea eliminar
			+--------------------------------------------
				Notas:
					esta es la unica funcion de eliminar un alias individualmente
					los espacios en blanco al principio y al final de enlace en el caso de se un tring y alias son eliminados
			+--------------------------------------------
				Cuerpo:
				*/	
			public function eliminar_alias($alias) {
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->eliminar_alias(alias) el iterador no esta ocupado");
					return false;
				}
				$alias=trim($alias);
				if (($todoslosalias=$cuerpo->adyacente("alias")) and ($nodo1=$todoslosalias->adyacente($alias))){
					$enlace=$nodo1->dato();
					if (($todoslosenlacesalias=$cuerpo->adyacente("enlaces alias")) and ($nodo2=$todoslosenlacesalias->adyacente($enlace))){
						$todoslosalias->eliminar_enlace($alias);
						Nodo::eliminar($nodo1);
						$todoslosenlacesalias->eliminar_enlace($enlace);
						Nodo::eliminar($nodo2);
					}else{
						$this->_alerta("no existe el alias que intenta eliminar(1)");
						return false;
					}
				}else{
					$this->_alerta("no existe el alias que intenta eliminar(2)".$alias);
					return false;
				}
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: quedo eliminado el alias
			+--------------------------------------------
		<-----------------------Fin de eliminar_alias($alias)*/

		/*FUNCION protected _varios_alias($arreglo_alias)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: agregar varios alias
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$arreglo_alias: tiene que ser un arreglo con los alias como llave y los enlace asociados a cada llave
			+--------------------------------------------
				Notas:
					asigna un arreglo de alias
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _varios_alias($arreglo_alias){
				/*if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_varios_alias(arreglo_alias) el iterador no esta ocupado");
					return false;
				}*/
				if (!is_array($arreglo_alias)){
					$this->_error("Iterador->_varios_alias(arreglo_alias) debe recibir un arreglo cuyo indice sean strings (los alias) y los valores sean los nombres de los enlaces (string o enteros)");
					return false;
				}
				$error=false;
				foreach ($arreglo_alias as $alias => $enlace){
					//echo "DD".$enlace."=>".$alias."FF";
					if (!$this->_alias($enlace, $alias)){
						$error=true;
					}
				}
				if ($error){
					$this->_error("Iterador->_varios_alias(arreglo_alias) uno o varios pares (alias, enlace) del arrelo pasado como parametro a _varios_alias(arreglo_alias) no es valido");
					return false;
				}else{
					return true;
				}
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: fueron agregados los alias
			+--------------------------------------------
		<-----------------------Fin de _varios_alias($arreglo_alias)*/

		/*FUNCION public eliminar_todos_los_alias()------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: elimina todos los alias
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:

			+--------------------------------------------
				Notas:
					asigna un arreglo de alias
			+--------------------------------------------
				Cuerpo:
				*/	
			public function eliminar_todos_los_alias(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->eliminar_todos_los_alias() el iterador no esta ocupado");
					return false;
				}
				if ((!$nalias=$cuerpo->adyacente("alias")) or (!$nenlacesalias=$cuerpo->adyacente("enlaces alias"))){
					$this->_alerta("Iterador->eliminar_todos_los_alias() el iterador no tenia ningun alias para eliminar");
					return true;
				}
				if (!$nalias->por_cada_adyacente_ejecutar(
					function($nodo,$enlace,$nalias){
						$nodoaelim=$nalias->adyacente($enlace);
						$nalias->eliminar_enlace($enlace); 
						if(!Nodo::eliminar($nodoaelim)){
							$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar alguno, varios o todos los alias(1)");
						}
						}, 
					$nalias)){
					$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar alguno, varios o todos los alias(2)");
					return false;
				}else{
					$cuerpo->eliminar_enlace("alias");
					Nodo::eliminar($nalias);
				}
				//$nenlacesalias->imprimir();
				if (!$nenlacesalias->por_cada_adyacente_ejecutar(
					function($nodo,$enlace,$nenlacesalias){
						//$nodo->imprimir();
						//echo "****".$enlace."***";
						$nodoaelim=$nenlacesalias->adyacente($enlace);
						$nenlacesalias->eliminar_enlace($enlace); 
						if(!Nodo::eliminar($nodoaelim)){
							$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar alguno, varios o todos los alias(3)");
						}
					}, 
					$nenlacesalias)){
					$this->_error("Iterador->eliminar_todos_los_alias() no se pudieron eliminar alguno, varios o todos los alias(4)");
					return false;
				}else{
					$cuerpo->eliminar_enlace("enlaces alias");
					Nodo::eliminar($nenlacesalias);
				}
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: fueron eliminados los alias
			+--------------------------------------------
		<-----------------------Fin de eliminar_todos_los_alias()*/

		/*FUNCION public enlace($alias)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: retorna el enlace asociado con ese alias
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias a partir del cual se decea obtener el enlace. Tiene que ser un string o un entero
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
				*/	
			public function enlace($alias){
	/*		hasta 1.9.4.191106
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->enlace($alias) el iterador no esta ocupado");
					return false;
				}
				if ((!is_int($alias)) and (!is_string($alias))){
					$this->_error("Iterador->enlace(alias) el alias tiene que ser un entero o un string");
					return null;
				}
				if (is_string($alias)){
					$alias=trim($alias);
				}
				if ($nalias=$cuerpo->adyacente("alias")){//tiene alias registrados
					if($nodo=$nalias->adyacente($alias)){//existe el alias registrado
						return $nodo->dato();
					}
				}
				return $alias;*/
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->enlace($alias) el iterador no esta ocupado");
					return false;
				}

				if (!$this->es_alias_valido($alias, $this)){
					Iterador::_error("Iterador->enlace($alias) el alias no es valido o no est� permitido");
					return false;
				}
				if ($nalias=$cuerpo->adyacente("alias")){//tiene alias registrados
					if($nodo=$nalias->adyacente($alias)){//existe el alias registrado
						return $nodo->dato();
					}
				}
				return $alias;
			}
			/*-------------------------------------------
				Datos de salida: devuelve un enlace correspondiente a un alias, si el par�metro no es un alias registrado devuelve al propio par�metro
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de enlace($alias)*/	
		/*FUNCION public alias($enlace)------------------------------------------------------>
				Interfaz: manejo de ALIAS
			+--------------------------------------------
				Caso de uso: retorna el alias asociado con ese enlace
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$enlace: el enlace a partir del cual se decea obtener el alias. Tiene que ser un string o un entero
			+--------------------------------------------
				Notas: esta funcion es la contraria a enlace(alias)
			+--------------------------------------------
				Cuerpo:
				*/	
			public function alias($enlace){
	/*		hasta 1.9.4.191106
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->enlace($alias) el iterador no esta ocupado");
					return false;
				}
				if ((!is_int($alias)) and (!is_string($alias))){
					$this->_error("Iterador->enlace(alias) el alias tiene que ser un entero o un string");
					return null;
				}
				if (is_string($alias)){
					$alias=trim($alias);
				}
				if ($nalias=$cuerpo->adyacente("alias")){//tiene alias registrados
					if($nodo=$nalias->adyacente($alias)){//existe el alias registrado
						return $nodo->dato();
					}
				}
				return $alias;*/
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->alias($enlace) el iterador no esta ocupado");
					return false;
				}

				/*if (!$this->es_alias_valido($alias, $this)){
					Iterador::_error("Iterador->alias($enlace) el alias no es valido o no est� permitido");
					return false;
				}*/
				if ($nenlacesalias=$cuerpo->adyacente("enlaces alias")){//tiene alias registrados
					if($nodo=$nenlacesalias->adyacente($enlace)){//existe el alias registrado
						return $nodo->dato();
					}
				}
				return $enlace;
			}
			/*-------------------------------------------
				Datos de salida: devuelve un alias correspondiente a un enlace, si no hay ningun alias registrado para ese enlace se devuelve devuelve al propio enlace
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de alias($enlace)*/	

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Actual ---------------------------------------->
	//------------------------------------------------------------------------------->
	//--V1.8------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
		*/

		/*FUNCION actual()------------------------------------------------------>
				Interfaz: Actual
			+--------------------------------------------
				Caso de uso: Obtener el nodo marcado como la posicion "actual" del Iterador
			+--------------------------------------------
				Precondiciones: Que el iterador este ocupado
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function actual(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->actual() el iterador no esta ocupado");
					return false;
				}
				$act=$cuerpo->adyacente("actual");
				if (!$act){
					$this->_alerta("Iterador->actual() el Iterador no tiene asignado ninguna posicion actual");
					return null;		
				}
				return $act;
			}
			/*-------------------------------------------
				Datos de salida: el nodo en la posicion "actual" del iterador. Null si no existe o en el caso de error.
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de actual()*/

		/*FUNCION _actual($elemento=null,&$es_nodo=null)------------------------------------------------------>
				Interfaz: Actual
			+--------------------------------------------
				Caso de uso: Asignar la posicion "actual" del iterador
			+--------------------------------------------
				Precondiciones: Que el iterador este ocupado
			+--------------------------------------------
				Datos de entrada:
					$elemento (opcional): debe pasar la prueba es_elemento_valido de la clase, si no se pasa ningun elemento intenara asignar un nodo "vacio"
					&es_nodo (opcional): devuelve true o false dependiendo si el elemento pasado por parametro es un nodo o no.
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function _actual($elemento=null,&$es_nodo=null){
				//inicializo la raiz
				/*	$ini=false;
					if (!isset($this->raiz_cuerpo)){
						$this->raiz_cuerpo=Nodo::crear();
						$ini=true;
					}*/
				//echo "F3F";
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_actual(elemento=null, &es_nodo=null) el iterador no esta ocupado");
					return false;
				}
				/*if (!$this->es_elemento_valido($elemento)){
					$this->_error("Iterador->_actual(elemento) el no pasa la prueba de es_elemento_valido");
					return null;
				}*/
				//echo "F5F";
			/*	if ($this->raiz_cuerpo->adyacente("actual")){//180606
					$this->raiz_cuerpo->eliminar_enlace("actual");
				}*/
				$nodo=null;
				if (!$nodo=$this->nodo($elemento, $es_nodo)){
					$this->_error("Iterador _actual(elemento=null, &es_nodo=null), posiblemente elemento no sea valido");
					return null;
				}
				if ($cuerpo->adyacente("guardar recorrido")){
					$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
					$this->guardar_visitado_interno($ndatos, $nodo);
				}
				return	$cuerpo->_adyacente_en($nodo, "actual");

			}
			/*-------------------------------------------
				Datos de salida: el nodo actual si tuvo exito. null en el caso de error en el Iterador. False en el caso de problema con un_nodo
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de  _actual($elemento=null,&$es_nodo=null)*/

	//////////////////////////////////////////////////////////////////////////////////

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Avanzar --------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*Notas generales de la interfaz:
			PARA LA VERSION 3.0 se propone que avanzar aumente sus poderes en dos etapas:
				primero: aumentar el poder de movimiento a�adiendo un simbolo que indiques opciones de enlaces a seguir basadas en comprarar el dato del nodo actual con un string en la cadena-camino. VER MAQUINAS DE ESTADOS.
				segundo: a�adir un simbolo que permita ejecutar una funcion, esta funcion debera estar registrada previamente, con lo cual se propone un registro de funciones y "acortadores de nombres" que permitan referenciarlas desde la cadena-camino. Para ingresarle datos a estas funciones dbeera considerarse utlizar el registro de datos que tiene cada iterador.

		*/

		/*FUNCION camino($cadena)------------------------------------------------------>
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: Convertir cadena en camino de nodos
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$cadena: un string con la sintaxis adecuada que sera transformado en cadena de nodos
			+--------------------------------------------
				Notas: Ver la estructura interna del camino en "Estructura de camino avanzar interno".
					El camino consiste en sentencias separadas por punto y coma ; Por ahora se permiten dos operaciones * y >, la ultima es para avanzar, acepta un parametro seguidamente al simbolo que tiene que ser un numero entero.
			+--------------------------------------------
				Cuerpo:
			*/
			public function camino($cadena){
				$i=0;
				//$j=0;
				$fin=strlen($cadena);
				$cantidad=0;
				//leo un enlace
				$eslabonant=Nodo::crear();
				$res=$eslabonant; 
				//el primer nodo tendra el alias vacio. el siguiete tendra realmente el alias
				//$i++;
				while ($i!=$fin){	
					$eslabontext="";
					$c=$cadena[$i];
					//si llego hasta aqui lo primero que tiene que leer es el alias
					if (($c==">")or($c==";")){
						$this->_error("Iterador->private camino(cadena). error de sintaxis(1)");
						//echo "!!!!1!!";
						$this->eliminar_camino($res);
						return null;
					}
					//leo el alias					
					if ($c=="/"){
						$i++;
						$eslabontext.=$c;
						if ($i==$fin){
							$this->_error("Iterador->private camino(cadena). error de sintaxis(2)");
							//echo "!!!!1!!";
							$this->eliminar_camino($res);
							return null;						
						};
					}
					$c=$cadena[$i];
					$aliastext=$c;					
					$i++;
					$eslabontext.=$c;
					//$j++;
					$fin2=false;
					while (($i!=$fin)and(!$fin2)){
						$c=$cadena[$i];
						if (($c!=";")&&($c!=">")){
							if ($c=="/"){
								$i++;
								$eslabontext.=$c;
								if ($i==$fin){
									$this->_error("Iterador->private camino(cadena). error de sintaxis(3)");
									//echo "!!!!1!!";
									$this->eliminar_camino($res);
									return null;						
								};
								$c=$cadena[$i];
							}
							$aliastext=$aliastext.$c;
							
							//$c=$cadena{$i};
							$i++;
							$eslabontext.=$c;
						}else{
							$fin2=true;
						}
					}
					$alias= Nodo::crear_con_dato($aliastext);
					$eslabonnue= Nodo::crear();
					$eslabonant->_adyacente_en($eslabonnue, "eslabon");
					$eslabonnue->_adyacente_en($alias, "alias");					
					$eslabonant=$eslabonnue;					
					//leo simbolo
					$creosim=false;
					//$
					$simbolo;
					if ($i!=$fin){
						$c=$cadena[$i];
						if ($c!=";"){
							$simbolotext=$c;
							$alias->_adyacente_en($simbolo=Nodo::crear_con_dato($c), "simbolo");
							$i++;
							$eslabontext.=$c;
							$creosim=true;
						}
					}
					//leo parametro
					$parametrotext="";
					if (($creosim)&&($i!=$fin)){
						$c=$cadena[$i];
						if ($c!=";"){
							//$simbolo=$alias->adyacente("simbolo");
							if ($c=="/"){
								$i++;
								$eslabontext.=$c;
								if ($i==$fin){
									$this->_error("Iterador->private camino(cadena). error de sintaxis(2.1)");
									//echo "!!!!1!!";
									$this->eliminar_camino($res);
									return null;						
								};
							}
							$c=$cadena[$i];
							$parametrotext.=$c;
							$i++;
							$eslabontext.=$c;
							$fin3=false;
							while (($i!=$fin)&&(!$fin3)){
								$c=$cadena[$i];
								if (($c!=";")&&($c!=">")){
									if ($c=="/"){
										$i++;
										$eslabontext.=$c;
										if ($i==$fin){
											$this->_error("Iterador->private camino(cadena). error de sintaxis(4)");
											//echo "!!!!1!!";
											$this->eliminar_camino($res);
											return null;						
										};
										$c=$cadena[$i];
									}
									$parametrotext.=$c;
									$i++;
									$eslabontext.=$c;
								}else{
									$fin3=true;
								}
							}
							$simbolo->_adyacente_en(Nodo::crear_con_dato($parametrotext), "parametro");
						}
					}
					$puntoycomatext="";
					//leo punto y coma
					if ($i!=$fin){
						$c=$cadena[$i];
						if ($c!=";"){
							$this->_error("Iterador->private camino(cadena). error de sintaxis(5)");
							//echo "!!!!2!!";
							$this->eliminar_camino($res);
							return null;
						}else{
							$puntoycomatext=$c;
						}
					}
					if ($i!=$fin){
						$i++;
						$eslabontext.=$c;
						//echo "j";
					}
					$cantidad++;
					$eslabonnue->_dato($eslabontext);
				}
				$res->_dato($cantidad);
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: 
					Un nodo a modo de "cabeza" del camino si tubo exito, null en caso contrario.
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de camino(cadena)*/
		
		/*FUNCION eliminar_camino($nodo)------------------------------------------------------>
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: Convertir cadena en camino de nodos
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$nodo: el nodca-cabeza de la cadena-camino de nodos que se desea eliminar
			+--------------------------------------------
				Notas: Ver la estructura interna del camino en "Estructura de camino avanzar interno".
					El camino consiste en sentencias separadas por punto y coma ; Por ahora se permiten dos operaciones * y >, la ultima es para avanzar, acepta un parametro seguidamente al simbolo que tiene que ser un numero entero.
			+--------------------------------------------
				Cuerpo:
			*/
			private function eliminar_camino($nodo){
				//$entrada=$nodo;
				while ($sig=$nodo->adyacente("eslabon")){
					if (!Nodo::eliminar($nodo)){
						$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (1)");
					};
					if ($alias=$sig->adyacente("alias")){
						$sig->eliminar_enlace("alias");
						if ($sim=$alias->adyacente("simbolo")){
							$alias->eliminar_enlace("simbolo");
							if ($par=$sim->adyacente("parametro")){
								$sim->eliminar_enlace("parametro");
								if (!Nodo::eliminar($par)){
									$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (2)");
								};
							}
			
							if (!Nodo::eliminar($sim)){
								$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (3)");
							};
						}
						if (!Nodo::eliminar($alias)){
							$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (4)");
						};
					}
					$nodo=$sig;
				}
				if (!Nodo::eliminar($nodo)){
					$this->_error("Iterador->private eliminar_camino no se pudo eliminar el nodo (5)");
				};
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de eliminar_camino($nodo)*/
		/*FUNCION  avanzar_especial($caracter) ---------------------------------------->
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: verifica que el caracter sea un simbolo especial para la funcion avanzar o no
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$caracter : el caracter a verificar
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/		
			private function avanzar_especial($caracter){
				$res=false;
				switch ($caracter){
					case ";":
					case ">":
					//case "/":
					case "*":
					//case ":":
					//case ")":
						$res=true;
					}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: //retorna true o false dependiendo si el caracter pasado por parametro es especial o no
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de  avanzar_especial($caracter) */
			/*FUNCION  avanzar_escapar($string) ---------------------------------------->
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: escapar string. agrega / para escapar caracteres especiales a un string
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$string : una cadena con caracteres especiales que se desean escapar
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			public function avanzar_escapar($string){
				if ((!is_string($string)) and ($string!="")){
					$this->_error("Iterador->avanzar_escapar(string)el argumento pasado por parametro debe ser un string");
					return null;
				}
				$stringres="";
				$posres=0;
				//$pos=0;
				$stringlength=strlen($string);
				for ($pos=0; $pos<$stringlength; $pos++){
					$caracter=$string[$pos];
					if ($this->avanzar_especial($caracter)or ($caracter=="/")){
						$stringres[$posres]="/";
						$posres++;
					}

					$stringres[$posres]=$caracter;
					$posres++;
				}
				
				/*echo implode($stringres);
				return  implode($stringres);*/
				return $stringres;
			}
			/*-------------------------------------------
				Datos de salida: un string con / delante de cada caracter "especial" encontrado
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de  avanazar_especial($caracter) */
		/*FUNCION avanzar_interno($cadena)------------------------------------------------------>
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: Avanzar interno
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$cadena: un string con la sintaxis adecuada que indica el camino por el que se desea avanzar
					$cant (opcional): este numero indicara la cantidad de "eslabolnes" de la cadena camino que se deberan avanzar. Si es positivo la cuenta arranca desde el primer eslabon; si es negativo, la cuenta arrnca desde el ultimo hacia atras.
					&$camino_recorrido (opcional): la parte de la cadena camino por la que se avanzo.
					&$camino_restante (opcional): la parte de la cadena camino que resto recorrer.
			+--------------------------------------------
				Notas:
					Ver la estructura interna del camino en "Estructura de camino avanzar interno".
					El camino consiste en sentencias separadas por punto y coma ; Por ahora se permite operaciones  > para avanzar, acepta un parametro seguidamente al simbolo que tiene que ser un numero entero.
				Notas viejas:
					voy a encapsular todo el proceso con los caminos en una sola funcion para que sea mas facil luego quitarla de la clase. O mas facil realizar cambios.
				Requerimientos de la funcion:
					recorrer un camino hasta el final 
					en el caso de que no exista el camino la funcion debera frenar, volver a la posicon original y devolver un alerta		
				Nuevos requisitos 191222:
					Pasar por variable la cantidad de posiciones de la cadena camino avanzar. Este numero sera un entero. Si es positivo, la cuenta arrancara desde la primera posicion; si es negativo arrancara desde el final.
					Agregar dos variables de entrada pasadas por referencia: camino_recorrido y camino_restante. Que devuelva en todos los casos los dos fragmentos de camino: el que se recorrio y el que falto.
			+--------------------------------------------
				Cuerpo:
			*/
			public function avanzar_interno($cadena, $cant=null, &$camino_recorrido=null, &$camino_restante=null){
				//echo "AA";
				$ant=null;
				$cuerpo=$this->raiz_cuerpo;
				$anterior=$cuerpo->adyacente("actual");
				$origen=$anterior;
				$camino=null;
				
				//ver si existe el nodo con id especial "caminos registrados" 1.9.4.191204
				If (!$ncaminos=Nodo::nodo_por_id("caminos registrados")){
					$ncaminos=Nodo::crear_con_id("caminos registrados");
				}
				//$cadena=trim($cadena);
				//verifico si existe el camino registrado 1.9.4.191204
				$yaestaba=false;
				//$canttotal
				if (!$camino=$ncaminos->adyacente($cadena)){
					if (!$camino=$this->camino($cadena)){
						$this->_error("Iterador->avanzar_interno() no se pudo validar la cadena pasada como parametro");
						return false;		
					}
					/*else{ //ahora lo voy a guardar solo si el camino es correcto, por eso elimino estas sentencias
						$ncaminos->_adyacente_en($camino, $cadena);
					}*/
				}else{
					$yaestaba=true;
				}

				/*eliminado 1.9.4.191204
				if (!$camino=$this->camino($cadena)){
					$this->_error("Iterador->avanzar_interno() no se pudo validar la cadena pasada como parametro");
					return false;		
				};*/

				//echo "BB";
				$camino_recorrido="";
				$camino_orig=$camino;
				$alias=null;
				$canttotal=$camino->dato();
				//variable que mira a ver si va a quedar un pedaso de la cadena sin recorrer
				$sobra=false;
				if ($cant and is_int($cant)){
					if ($cant>0){
						if ($cant<$canttotal){
							$cantarecorrer=$cant;
							$sobra=$canttotal-$cant;
						}elseif ($cant==$canttotal){
							$cantarecorrer=$canttotal;
						}else{
							$this->_error("Iterador->avanzar_interno() la cantidad de eslabones de la cadena a recorrer no puede ser mayor al total de eslabones de la cadena (1)");
							//echo "F1F";
							if (!$yaestaba){
								$this->eliminar_camino($camino_orig);
							}
							//$cuerpo->_adyacente_en($origen, "actual");//************
							//echo "F2F";
							return false;
						}
					}else{
						$resaux=$canttotal+$cant;
						if ($resaux<$canttotal){
							$cantarecorrer=$resaux;
							$sobra=-$cant;
						}elseif ($resaux==$canttotal){
							$cantarecorrer=$canttotal;
						}else{
							$this->_error("Iterador->avanzar_interno() la cantidad de eslabones de la cadena a recorrer no puede ser mayor al total de eslabones de la cadena (2)");
							//echo "F1F";
							if (!$yaestaba){
								$this->eliminar_camino($camino_orig);
							}
							//$cuerpo->_adyacente_en($origen, "actual");//************
							//echo "F2F";
							return false;
						}
					}
				}else{
					$cantarecorrer=$canttotal;
				}
				$cantrecorrido=0;
				while ($camino=$camino->adyacente("eslabon") and ($cantrecorrido<$cantarecorrer)){
					//echo "CC";
					//$simb;
					//echo "entro";
					$alias=$camino->adyacente("alias");
					$simb=$alias->adyacente("simbolo");
					if(!$enlace=$this->enlace($alias=$alias->dato())){
						$this->_error("Iterador->avanzar_interno() error el alias ".$alias." no estapermitido. Camino recorrido: ".$camino_recorrido);
							//echo "F1F";
						if (!$yaestaba){
							$this->eliminar_camino($camino_orig);
						}
						$cuerpo->_adyacente_en($origen, "actual");//************
							//echo "F2F";
						return false;						
					};
					if ($simb==null){
						//echo "DD";
						//echo "es nulo".$this->enlace($enlace); 
						//$anterior->imprimir();
						$anterior=$cuerpo->adyacente("actual");
						if ($sig=$anterior->adyacente($enlace)){
							//echo "EE";
							$cuerpo->_adyacente_en($sig, "actual");//**************
							//$camrec=$camrec.$enlace.";";//AVANZO
						}else{
							//echo "FF";
							$this->_error("Iterador->avanzar_interno() no existe adyacente en ".$enlace.". Camino recorrido: ".$camino_recorrido);
							if (!$yaestaba){
								$this->eliminar_camino($camino_orig);
							}
							//echo "F1F";
							$cuerpo->_adyacente_en($origen, "actual");//************
							//echo "F2F";
							return false;			
						}	
						//$this->_actual();
					}else {
						//echo "GG";
						switch ($simb->dato()){
							case ">": // echo "HH";
								//puede o no existir un parametro
								if ($nodopar=$simb->adyacente("parametro")){
									//echo "II";
									$par=$nodopar->dato();
									//echo "*******".$par."******";
									if(!is_numeric($par)){

										$this->_error("Iterador->avanzar_interno() error de sintaxis, el parametro despues de > tiene que ser un numero entero. Camino recorrido: ".$camino_recorrido);

										if (!$yaestaba){
											$this->eliminar_camino($camino_orig);
										}
										$cuerpo->_adyacente_en($origen, "actual");//**************
										return false;							
									}
									$i=1;
									//$fat=false; //finaliza antes de tiempo
								
									while ($i<=$par){
										$anterior=$cuerpo->adyacente("actual");
										if ($sig=$anterior->adyacente($enlace)){
											$cuerpo->_adyacente_en($sig, "actual");//*****************
											//$camrec=$camrec.$enlace.";";//AVANZO
										}else{

											$this->_error("Iterador->avanzar_interno() no existe adyacente en ".$enlace.". Camino recorrido: ".$camino_recorrido);
											//echo "sapoooopopopo";
											if (!$yaestaba){
												$this->eliminar_camino($camino_orig);
											}
											$cuerpo->_adyacente_en($origen, "actual");//***********
											return false;			
										}
										$i++;
									}


								}else{
									//echo "JJ";
									$fin=false;
										
									while(!$fin){
										//echo "KK";
										$anterior=$cuerpo->adyacente("actual");
										if ($sig=$anterior->adyacente($enlace)){
											//echo "MM";
											$cuerpo->_adyacente_en($sig, "actual");//******
											//$camrec=$camrec.$enlace.";";//AVANZO
										}else{
											//echo "NN";
											$fin=true;	
										}		
									}
									//echo "KKK".$camrec."KKK";
								}
								//echo "OO";
								break;
						}//fin swith

					}//fin else if
					$cantrecorrido++;
					$camino_recorrido.=$camino->dato();
				}//fin while
				//echo "PP";
				//$t_ini=microtime(true);
				// 1.9.4.191204 $this->eliminar_camino($camino_orig);
				/*$t_fin=microtime(true);
				$t=$t_fin-$t_ini;
				echo "Tiempo de crear:".$t;*/
				//echo "QQ";
				//si salio todo bien guardo ese camino
				if (!$yaestaba){
					$ncaminos->_adyacente_en($camino_orig, $cadena);
				}
				if ($sobra){
				//	echo "999".$camino->dato()."999".$sobra."555";
					$sig=$camino;
					$camino_restante.=$camino->dato();
					while ($sig=$sig->adyacente("eslabon")){
						$camino_restante.=$sig->dato();
					};
					if (!$ncaminos->adyacente($camino_restante)){
						$caminofal=Nodo::nodo($sobra);
						$caminofal->_adyacente_en($camino,"eslabon");
						$ncaminos->_adyacente_en($caminofal, $camino_restante);
					}
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true en el caso de existo, false en caso contrario
			+--------------------------------------------
				Poscondiciones:se recorrio el camino, en el caso de exito. en el caso de que no se haya podido completar el recorrido del camino, la posicon actual vuelve a la posicion desde donde partio
			+--------------------------------------------
		<-----------------------Fin de eliminar_camino($nodo)*/

		/*FUNCION avanzar($cadena)------------------------------------------------------>
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: Avanzar
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$cadena: un string con la sintaxis adecuada que indica el camino por el que se desea avanzar
					$cant (opcional): este numero indicara la cantidad de "eslabolnes" de la cadena camino que se deberan avanzar. Si es positivo la cuenta arranca desde el primer eslabon; si es negativo, la cuenta arrnca desde el ultimo hacia atras.
					&$camino_recorrido (opcional): la parte de la cadena camino por la que se avanzo.
					&$camino_restante (opcional): la parte de la cadena camino que resto recorrer.
			+--------------------------------------------
				Notas:
					Ver la estructura interna del camino en "Estructura de camino avanzar interno".
					El camino consiste en sentencias separadas por punto y coma ; Por ahora se permiten dos operaciones  > acepta un parametro seguidamente al simbolo que tiene que ser un numero entero.
			+--------------------------------------------
				Cuerpo:
			*/
			public function avanzar($camino, $cant=null, &$camino_recorrido=null, &$camino_restante=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->avanzar(camino) el iterador no esta ocupado");
					return false;
				}
				//$ant=null;
				//echo "XX1!";
				if (!$cuerpo->adyacente("actual")){
					$this->_error("Iterador->avanzar(camino) el iterador no tiene posici�n actual");
					return null;
				}
				//echo "XX2!";
				if (!$this->avanzar_interno($camino,$cant, $camino_recorrido, $camino_restante)){
					$this->_error("Iterador->avanzar(camino) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m&aacute;s informaci&oacute;n.");
					return null;
				}
				//echo "XX3!";
				$actual=$cuerpo->adyacente("actual");
				if ($cuerpo->adyacente("guardar recorrido")){
					$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
					$this->guardar_visitado_interno($ndatos, $actual);
				}
				return $actual;
			}
			/*-------------------------------------------
				Datos de salida: true en el caso de existo, false en caso contrario
			+--------------------------------------------
				Poscondiciones: se recorrio el camino, en el caso de exito. en el caso de que no se haya podido completar el recorrido del camino, la posicon actual vuelve a la posicion desde donde partio
			+--------------------------------------------
		<-----------------------Fin de avanzar($camino)*/

		/*FUNCION _avanzar($alias, $elemento=nulla, $camino=null, &$es_nodo) -------INSERTA-------------------->
				Interfaz: Avanzar
			+--------------------------------------------
				Caso de uso: Agregar adyacente y avanzar
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias o nombre del enlace en el que se desea agregar el un nodo y asignar como actual
					$elemento: (opcional) el elemento o nodo que se desea agregar a la estructura. Si no esta presente se agregara un nodo vacio.
					$camino: (opcional) el camino por el cual avanzar antes de agregar el elemento
					$es_nodo: (opcional) pasada como referencia. Devolvera si el elemento pasado como prametro es un nodo o no
					$cant (opcional): este numero indicara la cantidad de "eslabolnes" de la cadena camino que se deberan avanzar. Si es positivo la cuenta arranca desde el primer eslabon; si es negativo, la cuenta arrnca desde el ultimo hacia atras.
					&$camino_recorrido (opcional): la parte de la cadena camino por la que se avanzo.
					&$camino_restante (opcional): la parte de la cadena camino que resto recorrer.
			+--------------------------------------------
				Notas:
					//////////////////////V1.170121///////////////////////////////////
					//esta funcion insertando un nodo
			+--------------------------------------------
				Cuerpo:
			*/
			public function _avanzar($alias, $elemento=null, $camino=null, &$es_nodo=null, $cant=null, &$camino_recorrido=null, &$camino_restante=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) el iterador no esta ocupado");
					return false;
				}				
				$origen=null;
				//echo "1!";
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador-> _avanzar(alias, elemento, camino, es_nodo) el iterador no tiene asignada una posici�n actual");
					return null;
				}
				$enlace=null;
				//echo "2!";
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador-> _avanzar(alias, elemento, camino, es_nodo) no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino, $cant, $camino_recorrido, $camino_restante))){
					$this->_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
		/*		if (!$elemento){
					$nodo=Nodo::crear();
					$es_nodo=false;
					echo "hhhhhhhhhhhhhhhhhhhhhhhhhh";
				}else{*/
				if (!$nodo=$this->nodo($elemento,$es_nodo)) {
					$this->_error("Iterador->_avanzar(alias, elemento, camino, es_nodo) no se pudo validar el elemento para agregarlo a la estructura");
					//echo "paso por aca";
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;	
				}
				//}
				//echo "3!";
				$actual=$cuerpo->adyacente("actual");
				if($actual->adyacente($enlace)){
					//$this->actual()->eliminar_enlace($enlace);
					$this->_alerta("Iterador->_avanzar(alias, elemento, camino, es_nodo) se esta reemplazando un nodo en ese enlace");
				}
				$actual->_adyacente_en($nodo, $enlace);
				$cuerpo->_adyacente_en($nodo, "actual");
				//echo " aceeeeeeeeeer".$nodo->dato()."oo ";

				if ($cuerpo->adyacente("guardar recorrido")){
					$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
					$this->guardar_visitado_interno($ndatos, $nodo);
				}
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: 
					true en el caso de existo, false en caso contrario. 
					&es_nodo devolvera true o false dependiendo de si el elemento pasado como parametro era un nodo o no.
			+--------------------------------------------
				Poscondiciones: 
					si existia el parametro $camino se habra recorrido el camino.
					se inserto el elemento
					quedo posicionado en el elemento
			+--------------------------------------------
		<-----------------------Fin de _avanzar($alias, $elemento=null, $camino=null, &$es_nodo) */


	//////////////////////////////////////////////////////////////////////////////////

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Adyacente ------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*FUNCION _adyacente_en($elemento, $alias, $camino=null, &$es_nodo=null) ------------------------------------------>
				Interfaz: Adyacente
			+--------------------------------------------
				Caso de uso: Agrega un adyacente en un alias
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$elemento: el elemento o nodo que se decea agregar a la estructura. Es obligatorio.
					$alias: el alias o nombre del enlace en el se desea agregar el nodo. Es obligatorio
					$camino: (opcional) el camino por el cual avanzar antes de agregar el elemento
					&$es_nodo: (opcional) pasada como referencia. Devolvera si el elemento pasado como prametro es un nodo o no
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function _adyacente_en($elemento, $alias, $camino=null, &$es_nodo=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null,  &es_nodo=null) el Iterador no est� ocupado!!");
					return null;		
				}
				$origen=null;
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$actual=$cuerpo->adyacente("actual");
				$nodo=null;
				if (!$nodo=$this->nodo($elemento,$es_nodo)) {
					$this->_error("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el elemento para agregarlo a la estructura");
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;	
				}
				if($actual->adyacente($enlace)){
					//$this->actual()->eliminar_enlace($enlace);
					$this->_alerta("Iterador->_adyacente_en(elemento, alias, camino=null, &es_nodo=null) se esta reemplazando un nodo en ese enlace");
				}
				$actual->_adyacente_en($nodo, $enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: 
					el nodo con el elemento agregado en el caso de existo, null en caso contrario. 
					&es_nodo devolvera true o false dependiendo de si el elemento pasado como parametro era un nodo o no.
			+--------------------------------------------
				Poscondiciones: 
					se inserto el elemento
			+--------------------------------------------
		<-----------------------Fin de _adyacente_en(elemento, alias, camino=null, &es_nodo=null) */	

		/*FUNCION _adyacente($alias, $elemento=null, $camino=null,  &$es_nodo=null) ------------------------------------------>
				Interfaz: Adyacente
			+--------------------------------------------
				Caso de uso: Agrega un adyacente en un alias 2
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias o nombre del enlace en el se desea agregar el nodo. Es obligatorio
					$elemento: (opcional) el elemento o nodo que se decea agregar a la estructura. Si este elemento no esta, inter� insertar un elemento null o "vacio".
					$camino: (opcional) el camino por el cual avanzar antes de agregar el elemento
					&$es_nodo: (opcional) pasada como referencia. Devolvera si el elemento pasado como prametro es un nodo o no
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function _adyacente($alias, $elemento=null, $camino=null, &$es_nodo=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) el Iterador no est� ocupado!!");
					return null;		
				}
				$origen=null;
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return false;
				}
				$actual=$cuerpo->adyacente("actual");
				$nodo=null;
				if (!$nodo=$this->nodo($elemento,$es_nodo)) {
					$this->_error("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el elemento para agregarlo a la estructura");
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;	
				}
				if($actual->adyacente($enlace)){
					//$this->actual()->eliminar_enlace($enlace);
					$this->_alerta("Iterador->_adyacente(alias, elemento=null, camino=null, &es_nodo=null) se esta reemplazando un nodo en ese enlace");
				}
				$actual->_adyacente_en($nodo, $enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: 
					el nodo con el elemento agregado en el caso de existo, null en caso contrario. 
					&es_nodo devolvera true o false dependiendo de si el elemento pasado como parametro era un nodo o no.
			+--------------------------------------------
				Poscondiciones: 
					se inserto el elemento
			+--------------------------------------------
		<-----------------------Fin de _adyacente($alias, $elemento=null, $camino=null, &$es_nodo=null) */	

		/*FUNCION protected _adyacentes($arreglo_elementos, $camino=null)---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: agregar varios adyacente
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$arreglo_elementos: tiene que ser un arreglo con los alias como llave y los noso (o elementos) que se quieren agregar en cada alias o enlace
					$camino: (opcional) el camino por el cual avanzar antes de agregar los elementos
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _adyacentes($arreglo_elementos, $camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_varios_alias(arreglo_alias) el iterador no esta ocupado");
					return false;
				}
				$origen=null;
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_varios_alias(arreglo_alias) el iterador no tiene niguna asignado nodo actual");
					return null;
				}				
				if (!is_array($arreglo_elementos)){
					$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) debe recibir un arreglo cuyo indice sean strings (los alias) y los valores sean los elementos que se desean agreagar en cada enlace o alias (estos elementos pueden ser nodos o no)");
					return false;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return false;
				}
				$error=false;
				$actual=$cuerpo->adyacente("actual");
				foreach ($arreglo_elementos as $alias => $elemento){
					//echo "DD".$enlace."=>".$alias."FF";
					$enlace;
					$nodo;
					if ((!$enlace=$this->enlace($alias)) or (!$nodo=$this->nodo($elemento, $es_nodo))){
						$error=true;
					}else{
						$actual->_adyacente_en($nodo, $enlace);
					}
				}
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				if ($error){
					$this->_error("Iterador->_adyacentes(arreglo_elementos, camino=null) uno o varios pares (alias, elemento) del arrelo pasado como parametro a _adyacentes(arreglo_elementos, camino=null) no es valido");
					return false;
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: fueron agregados los elementos.
			+--------------------------------------------
		<-----------------------Fin de _adyacentes($arreglo_elementos, $camino=null)*/

		/*FUNCION public adyacentes($camino=null)---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: retorna todos los adyacentes
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$camino (opcional):  el camino por el cual avanzar antes de retornar los adyacentes
			+--------------------------------------------
				Notas: 
					//agregado en 1.3.7
			+--------------------------------------------
				Cuerpo:
				*/	
			public function adyacentes($camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->adyacentes($camino=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->adyacentes($camino=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}	
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->adyacentes($camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$actual=$cuerpo->adyacente("actual");
				function nodo($nodo){return $nodo;};
				$res= $actual->por_cada_adyacente_ejecutar("nodo");
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: un arreglo cuyas llaves son los enlaces y elementos cada uno de los nodos adyacentes si tubo exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de adyacentes($camino=null)*/

		/*FUNCION public adyacente($alias, $camino=null))---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: retorna adyacente
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias del enlace en el cual se encuentra el adyacente que se decea recuperar
					$camino (opcional):  el camino por el cual avanzar antes de retornar el adyacente
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			public function adyacente($alias, $camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->adyacente(alias, camino=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->adyacente(alias, camino=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->adyacente(alias, camino=null)no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->adyacente(alias, camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}	
				$actual=$cuerpo->adyacente("actual");
				$res=$actual->adyacente($enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen,"actual");
				}
				if (!$res){
					$this->_alerta("Iterador->adyacente(alias, camino=null) no existe adyacente en ese alias");
					return null;
				}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: el nodo adyacente en el alias pasado como parametro en el caso de exito. Null en caso contrario
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de adyacente($alias, $camino=null)*/
		
		/*FUNCION public eliminar_adyacente($alias, $camino=null---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: elimina un adyacente
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias del enlace que se desea eliminar
					$camino (opcional):  el camino por el cual avanzar antes de eliminar el enlace
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			public function eliminar_adyacente($alias, $camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->eliminar_adyacente(alias, camino=null) el iterador no esta ocupado");
					return false;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->eliminar_adyacente(alias, camino=null) el iterador no tiene niguna asignado nodo actual");
					return false;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->eliminar_adyacente(alias, camino=null)no se pudo validar el alias pasado como parametro");
					return false;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->eliminar_adyacente(alias, camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return false;
				}	
				$actual=$cuerpo->adyacente("actual");
				$elim=$actual->eliminar_enlace($enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen,"actual");
				}
				if (!$elim){
					$this->_error("Iterador->eliminar_adyacente(alias, camino) puede que no exista nodo en el enlace pasado como parametro");
					return false;
				}
				return $elim;
			}
			/*-------------------------------------------
				Datos de salida: el nodo adyacente en el alias que se acaba de eliminar. False en caso contrario
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de eliminar_adyacente($alias, $camino=null)*/

		/*FUNCION public eliminar_adyacentes($camino=null---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: elimina todos los adyacentes
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$camino (opcional):  el camino por el cual avanzar antes de eliminar los enlaces
			+--------------------------------------------
				Notas: 
					//V1.8.9
			+--------------------------------------------
				Cuerpo:
				*/	
			public function eliminar_adyacentes($camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->eliminar_adyacente(camino=null) el iterador no esta ocupado");
					return false;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->eliminar_adyacente(camino=null) el iterador no tiene niguna asignado nodo actual");
					return false;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->eliminar_adyacente(camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return false;
				}	
				$actual=$cuerpo->adyacente("actual");
				$res=null;
				if (!$actual->tiene_adyacente()){
					$res=true;
				}elseif ($actual->eliminar_enlaces()) {
					$res=true;
				}else{
					$this->_error("Iterador->eliminar_adaycentes(camino=null) no se pudieron eliminar enlaces");
					$res=false;
				}
				if ($avanzo){
					$cuerpo->_adyacente_en($origen,"actual");
				}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: true en el caso de exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: se eliminaron todos los enlaces adyacentes
			+--------------------------------------------
		<-----------------------Fin de eliminar_adyacentes($camino=null)*/

		/*FUNCION public _como_adyacente_de_nodo_en_alias($elemento, $alias, $camino=null, &$es_nodo=null) ---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: Agrega un enlace desde el elemento a la estructura
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$elemento: el elemento o nodo desde el cual se desea agregar un enlace a la estructura
					$alias: el alias del enlace que se queiere agregar en el elemento hacia la estructura
					$camino (opcional):  el camino por el cual avanzar antes de agregar el enlace
					&$es_nodo (opcional): en esta variable pasada como referencia se devolvera si el elemento era un nodo o no.
			+--------------------------------------------
				Notas: 
					//agregado en 1.3.9
			+--------------------------------------------
				Cuerpo:
				*/		
			public function _como_adyacente_de_nodo_en_alias($elemento, $alias, $camino=null, &$es_nodo=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$nodo=null;
				if (!$nodo=$this->nodo($elemento,$es_nodo)) {
					$this->_error("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null) no se pudo validar el elemento para agregarlo a la estructura");
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;	
				}
				$actual=$cuerpo->adyacente("actual");
				if($nodo->adyacente($enlace)){
					//$this->actual()->eliminar_enlace($enlace);
					$this->_alerta("Iterador->_como_adyacente_de_nodo_en_alias(elemento, alias, camino=null, &es_nodo=null)  se esta reemplazando un nodo en ese enlace");
				}
				$nodo->_adyacente_en($actual, $enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: el nodo al que se le agrego el enlace en el caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de _como_adyacente_de_nodo_en_alias($elemento, $alias, $camino=null, &$es_nodo=null) */

		/*FUNCION public _adyacente_inverso($alias, $elemento=null, $camino=null, &$es_nodo=null) ---------------------------------------->
				Interfaz: adyacente
			+--------------------------------------------
				Caso de uso: Agrega un enlace desde el elemento a la estructura 2
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias del enlace que se quiere agregar en el elemento hacia la estructura
					$elemento (opcional): el elemento o nodo desde el cual se desea agregar un enlace a la estructura
					$camino (opcional):  el camino por el cual avanzar antes de agregar en enlace
					&$es_nodo (opcional): en esta variable pasada como referencia se devolvera si el elemento era un nodo o no.
			+--------------------------------------------
				Notas: 
					//agregado en 1.9.3
			+--------------------------------------------
				Cuerpo:
				*/		
			public function _adyacente_inverso($alias, $elemento=null, $camino=null, &$es_nodo=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->__adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$enlace=null;
				if (!$enlace=$this->enlace($alias)){
					$this->_error("Iterador->_adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el alias pasado como parametro");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$nodo=null;
				if (!$nodo=$this->nodo($elemento,$es_nodo)) {
					$this->_error("Iterador->_adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null) no se pudo validar el elemento para agregarlo a la estructura");
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;	
				}
				$actual=$cuerpo->adyacente("actual");
				if($nodo->adyacente($enlace)){
					//$this->actual()->eliminar_enlace($enlace);
					$this->_alerta("Iterador->_adyacente_inverso(alias, elemento=null, camino=null, &es_nodo=null)  se esta reemplazando un nodo en ese enlace");
				}
				$nodo->_adyacente_en($actual, $enlace);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: el nodo al que se le agrego el enlace en el caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de _adyacente_inverso($alias, $elemento, $camino=null, &$es_nodo=null) */


	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- INTERFAZ Dato ------------------------------------------>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

		/*FUNCION public _dato($dato, $camino=null) ---------------------------------------->
				Interfaz: Dato
			+--------------------------------------------
				Caso de uso: Asigna un dato al nodo actual
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$dato: el dato que se desea asignar al nodo. Debe pasar la prueba es_elemento_valido de la clase
					$camino (opcional):  el camino por el cual avanzar antes de asignar el dato
			+--------------------------------------------
				Notas: 
					//Agregado en la V1.7.170304
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _dato($dato, $camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->_dato(dato, camino=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->_dato(dato, camino=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$es_nodo=null;
				$datoaux=$dato;
				if (!$this->es_elemento_valido($datoaux, $es_nodo)){
					$this->_error("Iterador->_dato(dato, camino=null) el dato pasado como parametro no pasa la prueva es_elemento_valido de la clase de Iterador");
					return null;	
				}
				if ($es_nodo){
					$this->_error("Iterador->_dato(dato, camino=null) el dato es un nodo. No se puede guardar un nodo dentro de un nodo!!");
					return null;			
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->_dato(dato, camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$actual=$cuerpo->adyacente("actual");
				$actual->_dato($datoaux);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $actual;
			}
			/*-------------------------------------------
				Datos de salida: el nodo con el dato asignado en el caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de _dato($dato, $camino=null) */

		/*FUNCION public dato($camino=null) ---------------------------------------->
				Interfaz: Dato
			+--------------------------------------------
				Caso de uso: Retorna el dato del nodo actual
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$camino (opcional):  el camino por el cual avanzar antes de retornar el dato
			+--------------------------------------------
				Notas: 
					//Agregado en la V1.7.170304
			+--------------------------------------------
				Cuerpo:
				*/	
			public function dato($camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->dato(camino=null) el iterador no esta ocupado");
					return null;
				}
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->dato(camino=null) el iterador no tiene niguna asignado nodo actual");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->dato(camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$res=$cuerpo->adyacente("actual")->dato();
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: el dato asignado en el nodo actual en el caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de dato($$camino=null) */

	
	
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Liberar 1.7.1  /////////////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*FUNCION liberar() ---------------------------------------->
				Interfaz: Liberar
			+--------------------------------------------
				Caso de uso: Liberar
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/
			//-----agregadas en 1.7.1------------------------------------------------------------------//
			//esta funcion deber� ser redefinida en arbol
			public function liberar(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::liberar() el iterador no esta ocupado");
					return false;
				}	
				$act=null;
				if (!$act=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->liberar() el Iterador ya estaba liberado");
					return null;
				}
				$cuerpo->_adyacente_en($cuerpo, "actual");
				return $act;
			}
				/*-------------------------------------------
				Datos de salida: retorna el nodo actual
			+--------------------------------------------
				Poscondiciones: el iterador queda liberado
			+--------------------------------------------
		<-----------------------Fin de liberar() */
	
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- Interfaz de ARBOLEADO //////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->	
	
		/*FUNCION convertir_semaforos_a_nodos($semaforos) ---------------------------------------->
				Interfaz: Arboleado
			+--------------------------------------------
				Caso de uso: Auxiliar, convierte semaforos a nodos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$semaforos : una cadena de "semaforos" separados por ;
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			private function convertir_semaforos_a_nodos($semaforos){
				$semaforosex=explode(";",$semaforos);
				$nodores=Nodo::crear();
				foreach ($semaforosex as $i => $semaforo){
					$semaforo=trim($semaforo);
					$nodores->_adyacente_en($nodores, $semaforo);
				}
				return $nodores;
			}
			/*-------------------------------------------
				Datos de salida: un nodo cuyos enlaces son los nombres de los semaforos y apuntan al mismo nodo
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin convertir_semaforos_a_nodos($semaforos) */
		
	//-----agregadas en 1.7.1------------------------------------------------------------------//

		/*FUNCION cargar_adyacentes_como_hijos($arbol,$nsemaforos=null) ---------------------------------------->
				Interfaz: Arboleado
			+--------------------------------------------
				Caso de uso: Auxiliar, carga adyacentes como hijos del arbol
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$semaforos (opcional) : una cadena de "semaforos" separados por ;
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/

			private function cargar_adyacentes_como_hijos($aux, $arbol,$nsemaforos){
				$agregar_hijo=function ($nodo, $enlace, $arbol, $nsemaforos){
					//$arbol=
					$res=null;
					$primero=$nsemaforos->dato();
					$enlace=$this->alias($enlace);
					if (!$nsemaforos->adyacente($enlace)){
						//echo "(1)".$enlace;
						$act=$arbol->actual();
						if ($primero=="primero"){
							$arbol->_hmi($res=Nodo::crear_con_dato($enlace));
							$nsemaforos->_dato("no");
						}else{
							$arbol->_hd($res=Nodo::crear_con_dato($enlace));
						}
						$arbol->actual()->_adyacente_en($nodo,"nodo");
						
							//$arbol->_actual($act);
							//$arbol->p();
						//return $res;
					}else{
						//echo "(2)";
						$act=$arbol->actual();
					
						if ($primero=="primero"){
							//$arbol->_hmi($res=Nodo::crear_con_dato($enlace));
							$nsemaforos->_dato("no");
						}else{
							//$arbol->_hd($res=Nodo::crear_con_dato($enlace));
						}
						//$arbol->actual()->_adyacente_en($nodo,"nodo");
						//$arbol->p();
						//$arbol->_actual($act);
						//return $res;		
						
					}
					return $res;
					
				};
				$nsemaforos->_dato("primero");
				$aux->por_cada_adyacente_ejecutar($agregar_hijo,$arbol,$nsemaforos);	
				if ($nsemaforos->dato()=="no"){
					$arbol->p();
				}

			}
			/*-------------------------------------------
				Datos de salida: un nodo cuyos enlaces son los nombres de los semaforos y apuntan al mismo nodo
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin cargar_adyacentes_como_hijos($arbol,$nsemaforos=null)*/
		
		/*FUNCION arbolear($alias, $semaforos=null, $camino=null) ---------------------------------------->
				Interfaz: Arboleado
			+--------------------------------------------
				Caso de uso: Arbolea la estructura
			+--------------------------------------------
				Precondiciones: que no haya bucles o nodos referenciados mas de una vez
			+--------------------------------------------
				Datos de entrada:
					$alias : alias del enlace a partir del cual se va a arbolear la estructura
					$semaforos (opcional) : debe ser una sucecion de nombre de enlaces separados por ";"
					$camino (opcional): un "camino" por el cual avanzar antes de intentar el arboleado
			+--------------------------------------------
				Notas: hace el intento
			+--------------------------------------------
				Cuerpo:
				*/
			public function arbolear($alias, $semaforos=null, $camino=null){
				
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->arbolear($alias, $semaforos=null, $camino=null) el iterador no esta ocupado");
					return null;
				}
				$ant=null;
				//echo "XX1!";
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->arbolear($alias, $semaforos=null, $camino=null) el iterador no tiene posici�n actual");
					return null;
				}
				//echo "*********1*".$cuerpo->adyacente("actual")->dato()."&&";
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->arbolear($alias, $semaforos=null, $camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				//echo "***********2*".$cuerpo->adyacente("actual")->dato()."&&";
				$enlace=$this->enlace($alias);
				$actual=$cuerpo->adyacente("actual");
				if (!$actual=$actual->adyacente($enlace)){
					$this->_error("Iterador->arbolear($alias, $semaforos=null, $camino=null), no existe enlace con ese alias: ".$alias);
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;
				}

				/*$actual=$this->actual();
				
				$this->avanzar($alias);*/
				
				$nivel=0;
				$idraiz=$actual->id();
				
				$fin=false;
				$visitados=Array();


				$arbol=Arbol::iterador("Iterador->arbolear");
				if (!$arbol){
					//echo "#tatatatata";
					return;
				}else{
					//echo "la primera entro";
				}
				if (!$arbol->vacio()){
					//echo "<br/>no esta vacio";
				/*	$arbol->raiz();
					if ($nnodo=$arbol->actual()->adyacente("nodo")){
						$arbol->actual()->eliminar_enlace("nodo");
						Nodo::eliminar($nnodo);
					}*/
					//$arbol->destruir_arbol();
				}else{
				//	echo "<br/>si vacio!";
				}
				//$arbol->destruir_arbol();

				$arbol->_raiz($narbol=Nodo::crear_con_dato($alias));
				$narbol->_adyacente_en($actual,"nodo");
				$nsemaforos=null;

				if (is_string($semaforos)){
					$nsemaforos=$this->convertir_semaforos_a_nodos($semaforos);//lo convierto a nodo
				}
				if (!$nsemaforos){
					$nsemaforos=Nodo::nodo();
				}

				while (!$fin){
					//echo "*1*";
					//echo "%".$this->actual()->dato()."%";
					$this->cargar_adyacentes_como_hijos($actual, $arbol, $nsemaforos);
					$idactual=$actual->id();
					//echo " ID:".$idactual."*";
					if (isset($visitados[$idactual])and $visitados[$idactual]){//est� repetido!
						//echo "*2*";
						//echo "ago algo con el actual";
						$arbol->destruir_arbol();
						//$this->_actual($origen);
						//$arbol->liberar();
						Nodo::eliminar_autoenlazado($nsemaforos);
						$arbol->desocupar();
						$this->_error("Iterador->arbolear($alias, $semaforos=null, $camino=null) la estructura no es arboleable(1)");
						return null;
					}else {//si no lo encontro avanzo por la opcion que pueda
						//echo "*3*";
						$visitados[$idactual]=true;
						if (!$arbol->hmi()) {
							//echo "*4*";
							
							if (!$arbol->hd()){
								//echo "*5*";
								$fin1=false;
								while ((!$fin)&&(!$fin1)){
									if ($arbol->p()){
										$nivel--;
										//echo "nivel-".$nivel;
										if($arbol->hd()){
											$actual=$arbol->actual()->adyacente("nodo");
											$fin1=true;
										}
										$actual=$arbol->actual()->adyacente("nodo");
									}else{
									//	echo "llego a la raiz??";
										$fin1=true;						
									}
								}
							}else{
								$actual=$arbol->actual()->adyacente("nodo");
							}	
						}else{
							$nivel++;
							//echo "nivel+".$nivel;
							$actual=$arbol->actual()->adyacente("nodo");
						}
					}
					//echo "*6*";
					$fin=$actual->id()==$idraiz;
				}			
				//echo "*7*";
				
				//$this->_actual($actual);
			//	$arbol->liberar();
				Nodo::eliminar_autoenlazado($nsemaforos);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				if ($nivel!=0){
					//echo " goga ";
					
					$arbol->destruir_arbol();
					//$arbol->liberar();
					$arbol->desocupar();
					//echo "nivelulu".$nivel;
					//Nodo::eliminar($nsemaforos);
					$this->_error("Iterador->arbolear($alias, $semaforos=null, $camino=null) la estructura no es arboleable(2)");
					return null;	
				}else{
					
					$res= $arbol->raiz();
					//Iterador::_error("Iterador->avanzar(camino) el iterador no esta ocupadhuiui7788o");
					$arbol->liberar();
					//Iterador::_error("Iterador->avanzar(camino) el iterador no esta ocupadhuiui77o");
					$arbol->desocupar();
					//Nodo::eliminar($nsemaforos);
					//echo "lkjkljlkjkljlkjljkljlkjlkjlkjkljljkljkljkl";
					return $res;
				}	
				
			}
			/*-------------------------------------------
				Datos de salida: la raiz de un "arboleado" creado a patir del enlace pasado como parametro
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin arbolear($alias, $semaforos=null, $camino=null)*/

		/*FUNCION es_arboleable($alias, $semaforos=null, $camino=null) ---------------------------------------->
				Interfaz: Arboleado
			+--------------------------------------------
				Caso de uso: Verifica si es arboleable
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias : alias del enlace a partir del cual se va a arbolear la estructura
					$semaforos (opcional) : debe ser una sucecion de nombre de enlaces separados por ";"
					$camino (opcional): un "camino" por el cual avanzar antes de intentar el arboleado
			+--------------------------------------------
				Notas: verifica si hay bucles o nodos referenciados mas de una vez
			+--------------------------------------------
				Cuerpo:
				*/
			public function es_arboleable($alias, $semaforos=null, $camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador->es_arboleable($alias, $semaforos=null, $camino=null) el iterador no esta ocupado");
					return null;
				}
				//$ant=null;
				//echo "XX1!";
				if (!$origen=$cuerpo->adyacente("actual")){
					$this->_error("Iterador->es_arboleable($alias, $semaforos=null, $camino=null) el iterador no tiene posici�n actual");
					return null;
				}
				$avanzo=false;
				if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador->es_arboleable($alias, $semaforos=null, $camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$enlace=$this->enlace($alias);
				//$enlace=$alias;
				$actual=$cuerpo->adyacente("actual");
				if (!$actual=$actual->adyacente($enlace)){
					$this->_error("Iterador->es_arboleable($alias, $semaforos=null, $camino=null), no existe enlace con ese alias: ".$alias);
					if ($avanzo){
						$cuerpo->_adyacente_en($origen, "actual");
					}
					return null;
				}

				/*$actual=$this->actual();
				
				$this->avanzar($alias);*/
				
				$nivel=0;
				$idraiz=$actual->id();
				
				$fin=false;
				$visitados=Array();


				$arbol=Arbol::iterador("Iterador->arbolear");
				if (!$arbol){
					//echo "#tatatatata";
					return;
				}else{
					//echo "la primera entro";
				}
				if (!$arbol->vacio()){
					//echo "<br/>no esta vacio";
				/*	$arbol->raiz();
					if ($nnodo=$arbol->actual()->adyacente("nodo")){
						$arbol->actual()->eliminar_enlace("nodo");
						Nodo::eliminar($nnodo);
					}*/
					//$arbol->destruir_arbol();
				}else{
				//	echo "<br/>si vacio!";
				}
				//$arbol->destruir_arbol();

				$arbol->_raiz($narbol=Nodo::crear_con_dato($alias));
				$narbol->_adyacente_en($actual,"nodo");
				$nsemaforos=null;

				if (is_string($semaforos)){
					$nsemaforos=$this->convertir_semaforos_a_nodos($semaforos);//lo convierto a nodo
				}
				if (!$nsemaforos){
					$nsemaforos=Nodo::nodo();
				}
				while (!$fin){
					//echo "*1*";
					//echo "%".$this->actual()->dato()."%";
					$this->cargar_adyacentes_como_hijos($actual, $arbol, $nsemaforos);
					$idactual=$actual->id();
					//echo " ID:".$idactual."*";
					if (isset($visitados[$idactual])and $visitados[$idactual]){//est� repetido!
						//echo "*2*";
						//echo "ago algo con el actual";
						$arbol->destruir_arbol();
						//$this->_actual($origen);
						//$arbol->liberar();
						$arbol->desocupar();
						//$this->_error("Iterador->Arbolear() la estructura no es arboleable(1)");
						Nodo::eliminar_autoenlazado($nsemaforos);
						if ($avanzo){
							$cuerpo->_adyacente_en($origen, "actual");
						}
						return false;
					}else {//si no lo encontro avanzo por la opcion que pueda
						//echo "*3*";
						$visitados[$idactual]=true;
						if (!$arbol->hmi()) {
							//echo "*4*";
							
							if (!$arbol->hd()){
								//echo "*5*";
								$fin1=false;
								while ((!$fin)&&(!$fin1)){
									if ($arbol->p()){
										$nivel--;
										//echo "nivel-".$nivel;
										if($arbol->hd()){
											$actual=$arbol->actual()->adyacente("nodo");
											$fin1=true;
										}
										$actual=$arbol->actual()->adyacente("nodo");
									}else{
									//	echo "llego a la raiz??";
										$fin1=true;						
									}
								}
							}else{
								$actual=$arbol->actual()->adyacente("nodo");
							}	
						}else{
							$nivel++;
							//echo "nivel+".$nivel;
							$actual=$arbol->actual()->adyacente("nodo");
						}
					}
					//echo "*6*";
					$fin=$actual->id()==$idraiz;
				}			
				//echo "*7*";
				
				//$this->_actual($actual);
			//	$arbol->liberar();
				Nodo::eliminar_autoenlazado($nsemaforos);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				if ($nivel!=0){
					//echo " goga ";
					
					$arbol->destruir_arbol();
					//$arbol->liberar();
					$arbol->desocupar();
					//echo "nivelulu".$nivel;
					//$this->_error("Iterador->Arbolear() la estructura no es arboleable(2)");
					return false;	
				}else{
					
					$arbol->destruir_arbol();
					//Iterador::_error("Iterador->avanzar(camino) el iterador no esta ocupadhuiui77o");
					$arbol->desocupar();
					
					return true;
				}	
				
			}
			/*-------------------------------------------
				Datos de salida: true si tuvo exito, false en caso contrario. Null si hubo error
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin es_arboleable($alias, $semaforos=null)*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- CONSTRUIR V1.6 /////////////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	//V1.6 CONSTRUIR////////////////////////////////////////////////////////
	
			/*FUNCION  construir_escapar($string) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: escapar string. agrega / para escapar caracteres especiales a un string
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$string : una cadena con caracteres especiales que se desean escapar
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			public function construir_escapar($string){
				if ((!is_string($string)) and ($string!="")){
					$this->_error("Iterador->construit_escapar(string)el argumento pasado por parametro debe ser un string");
					return null;
				}
				$stringres="";
				$posres=0;
				//$pos=0;
				$stringlength=strlen($string);
				for ($pos=0; $pos<$stringlength; $pos++){
					$caracter=$string[$pos];
					if ($this->construir_especial($caracter)or ($caracter=="/")){
						$stringres[$posres]="/";
						$posres++;
					}

					$stringres[$posres]=$caracter;
					$posres++;
				}
				
				/*echo implode($stringres);
				return  implode($stringres);*/
				return $stringres;
			}
			/*-------------------------------------------
				Datos de salida: un string con / delante de cada caracter "especial" encontrado
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de  construir_especial($caracter) */

		/*FUNCION  construir_nodo(&$cad) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: Contruye una estructura a partir de una cadena (nodo)
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$cad : cadena de nodos con caracteres
			+--------------------------------------------
				Notas: agregado en 1.6. auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
			private function construir_nodo(&$string, &$i, &$fin){
				//echo "-";
				$actual=$this->actual();
				//echo "AEEEEEEEEEEEE";
				$c=null;
				if ($i<$fin)	{
					$c=$string[$i];
				}else{
					$c=null;
				}
				$nodo=null;
				$dato=null;
			//	echo "//".$c."//";
				if ($c!="("){
					//leo un dato o valor
						if (!$dato=$this->construir_string($string, $i, $fin)){
							//$this->_error("Iterador->construir_nodo(icad) no se pudo construir dato(1)");
							//return null;
							if (!$nodo=$this->nodo()){
								$this->_error("Iterador->uno de los datos no esta permitido en esta clase de iterador (1)");
								return null;							
							};
						}else{
							if (!$nodo=$this->nodo($dato)){
								$this->_error("Iterador->uno de los datos no esta permitido en esta clase de iterador (2)");
								return null;							
							};
						}
				}else{
					$i++;
					if (!($i<$fin)){
						$this->_error("Iterador->construir_nodo(cad) final inesperado");
						return null;
					}
					$c=$string[$i];
					if ($c!=":"){
						///$i++;
						
						if (!$dato=$this->construir_string($string, $i, $fin)){
							$this->_error("Iterador->construir_nodo(cad) no se pudo construir dato");
							return null;
						}
						if (!$nodo=$this->nodo($dato)){
							$this->_error("Iterador->uno de los datos no esta permitido en esta clase de iterador (3)");
							return null;							
						};
						$c=$string[$i];
					}else{
						if (!$nodo=$this->nodo()){
							$this->_error("Iterador->uno de los datos no esta permitido en esta clase de iterador (4)");
							return null;							
						};
					
					}
					//echo "**".$c."**";
					if ($c!=":"){
						Nodo::eliminar($nodo);
						$this->_error("Iterador->construir_nodo(cad) se esperaban :");
						return null;
					}
					$i++;
					//$cad=$cad->adyacente("sig");

					$this->_actual($nodo);
					//echo "ZZZZZZZZZZZZZZZZ".$string{$i}."RR";
					if (!$this->construir_enlaces($string, $i, $fin)){
						$this->_actual($actual);
						Nodo::eliminar($nodo);
						$this->_error("Iterador->error al construir enlaces despues de los :".$string[$i]);
						return null;
					}
					if ($string[$i]==")"){
						//echo "lo cerro bien";
						//$cad=$cad->adyacente("sig");
						$i++;
					}
				}
				$this->_actual($actual);
				return $nodo;
			}
			/*-------------------------------------------
				Datos de salida: el nodo construido si tubo exito. null en caso contrario
			+--------------------------------------------
				Poscondiciones: queda construido el nodo y la cadena de caracteres en la posicon siguientea la del nodos
			+--------------------------------------------
		<-----------------------Fin de construir_nodo(&$cad) */

		/*FUNCION  construir_especial($caracter) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: verifica que el caracter sea un simbolo especial para la funcion construir o no
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$caracter : el caracter a verificar
			+--------------------------------------------
				Notas: agregado en 1.6. auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
			
			private function construir_especial($caracter){
				$res=false;
				switch ($caracter){
					case ";":
					case "=":
					//case "/":
					case "(":
					case ":":
					case ")":
						$res=true;
					}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: //retorna true o false dependiendo si el caracter pasado por parametro es especial o no
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de  construir_especial($caracter) */
		

		/*FUNCION construir_string(&$cad) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: contruye un string desde la cadena pasada por parametro
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$cad : cadena de nodos con caracteres
			+--------------------------------------------
				Notas: agregado en 1.6. auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
			public function construir_string(&$string, &$i, &$fin){

				$stringres="";
				$caracter=null;
				if ($i<$fin){
					$caracter=$string[$i];
				}else{
					$caracter="";
				}
				$finw=false;
			//	echo "**".$caracter."**";
				while (!$this->construir_especial($caracter) and !$finw){
				//	echo "A";
					if ($caracter=='/'){
					//	echo "B";
						$i++;
						if ($i<$fin){
					//		echo "C";
							$caracteraux=$string[$i];
						//	if ($this->construir_especial($caracteraux)or ($caracteraux=="/")){
								//echo "D";
								$stringres.=$caracteraux;
						//	}else{
								//echo "E";
						//		$string=$string.$caracter.$caracteraux;
					//		}
							//echo "F";
							$i++;
							if (!($i<$fin)){
					//			echo "F";
								$finw=true;
							}
							$caracter=$string[$i];
						}else{
					//		echo "G";
							//$string=$string.$caracter;
							$finw=true;
						}
					//	echo "H";
					}else{
					//	echo "I";
						$stringres.=$caracter;
						//$i++;
						$finw2=false;
						if (!($i<$fin)){

					//		echo "J";
							$finw=true;
						}else{
							$finw2=true;
							$i++;
						}
						if ($finw2 and !($i<$fin)){
							$finw=true;
						}elseif ($i<$fin){
							//echo "K".$string."L".$stringres;
							$caracter=$string[$i];
						}
					//	echo "M";
					}
				//	echo "N";
				}
				//echo "<br/>RES:".$stringres."i;".$i."fin:".$fin."<br/>";
				return $stringres;	
			}
			/*-------------------------------------------
				Datos de salida: un string listo para ser usado como alias o dato
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de construir_string(&$cad) */

		/*FUNCION construir_alias(&$cad) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: contruye un alias desde la cadena pasada por parametro
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$cad : cadena de nodos con caracteres
			+--------------------------------------------
				Notas: agregado en 1.6. auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
			//un nombre de enlace termina con =
		/*	private function construir_alias(&$cad){
				//echo "DDDDDDDDDDDDDDDDDDD";
				$alias=$cad->dato();
				if (!$cad=$cad->adyacente("sig")){
					$this->_error("Iterador->construir_nombre_enlace(cad) error de sintaxis, no se esperaba el fin de la cadena, se esperaba un signo =");
					return null;			
				}
				$caracter=$cad->dato();
				while ($caracter!="="){
					$alias=$alias.$caracter;
					if (!$cad=$cad->adycaente("sig")){
						$this->_error("Iterador->construir_nombre_enlace(cad) error de sintaxis se esperaba un = desdues del nombre del enlace");
						return null;			
					}
					$caracter=$cad->dato();
				}
			//	echo "PP".$alias;
				return $alias;
			}
			/*-------------------------------------------
				Datos de salida: un alias en el casa de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de construir_alias(&$cad) */

		/*FUNCION construir_dato(&$cad) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: contruye un dato desde la cadena pasada por parametro
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$cad : cadena de nodos con caracteres
			+--------------------------------------------
				Notas: agregado en 1.6. auxiliar
				auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
		/*	private function construir_dato(&$cad){
				//echo "AAAAAAAAAAAAAAAAAAAAAAA";
				$dato=$cad->dato();
				if (!$cad=$cad->adyacente("sig")){
					return $dato;			
				}
				$caracter=$cad->dato();
				//$alias="";
				while (($caracter!=":")and($caracter!=";")and($caracter!=")")){
					$dato=$dato.$caracter;
					if (!$cad->adyacente("sig")){
					//	echo "paso";
					//	echo ">".$dato;
						return $dato;			
					}
					$caracter=$cad->dato();
				}
			//	echo "YY".$dato;
				return $dato;
			}
			/*-------------------------------------------
				Datos de salida: un dato listo para ser usado en la estructura
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin de construir_dato(&$cad) */

		/*FUNCION  construir_enlaces($icad) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: Contruye una estructura a partir de una cadena. Mas adelante se va a especificar bien la estructura q tiene q tener la cadena
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$string : la cadena con informacion del o que se va a construir
					$camino (opcional):  el camino por el cual avanzar antes de intentar construir
			+--------------------------------------------
				Notas: agregado en 1.6 auxiliar de construir
			+--------------------------------------------
				Cuerpo:
				*/	
			private function construir_enlaces(&$string, &$i,&$fin){
				//if icad y res son iteradores y tienen posicion actual
			//	echo "ccccccccccccccccccccc";
				$actual=$this->actual();
				$cortar=false;
				$error=false;
				$exitosos=array();
				$cont=0;
				while (!$cortar){
					//echo "++a";
					if (!$alias=$this->construir_string($string, $i, $fin)){
						$this->_error("Iterador->construir_enlaces(cad) no se pudo contruir alias(1)".$string[$i]);
						$error=true;
						break;					
					}
			//		echo "b";
			//		echo $alias;
					if (!($i<$fin) or ($string[$i]!="=")){
						$this->_error("Iterador->construir_enlaces(cad) despues del nobre del enlace debe ir un signo =");
						$error=true;
						break;
					}
			//		echo "c";
					//leo el signo =
					$i++;
					//$cad=$cad->adyacente("sig");
				/*	if (!$icad->avanzar("sig")){
						$this->_error("Iterador->construir_enlaces(icad) despues del = debe ir un valor string o la representacion de un nodo");
						//return null;	
					}*/
			//		echo "d";
					if (!$nodo=$this->construir_nodo($string, $i, $fin)){
						$this->_error("Iterador->construir_enlaces(cad) se produjo un error intentando construir uno de los nodos");
						$error=true;
						break;			
					}	
			//		echo "e";
			//echo "NOOOOOOOOOOOOOOOOO".$alias."OOOOOOOOOOOOOOOOO";
					if (($this->adyacente($alias))and(!$this->destruir_estructura($alias))){
						$this->_error("Iterador->construir_enlaces(cad) se produjo un error destruir los datos preexistentes");
						$iaux=Iterador::iterador("Iterador->construir_enlaces");
						$nodoaux=Nodo::nodo();
						$iaux->_actual($nodoaux);
						$iaux->_adyacente_en($nodo,"destruir");
						$iaux->destruir_estructura("destruir");
						$iaux->liberar();
						$iaux->desocupar();
						Nodo::eliminar($nodoaux);
						$error=true;
						break;
					}
					$this->_adyacente_en($nodo, $alias);
					$exitosos[$cont]=$alias;
					$cont++;
					if (($i<$fin) and ($string[$i]==";")){
						//$cad=$cad->adyacente("sig");
						$i++;
					}
					/*else{
						$cortar=true;
					}*/
			//		echo "f";
				/*	$cortar2=false;
					if (!($i<$fin)){
						$cortar=true;
					}else{
						$cortar2=true;
						$i++;
					}*/

					if (((!($i<$fin)) or ($string[$i]==")"))){
						$cortar=true;
					} 
					/*
					if ( (!$cortar) and ($string{$i}==")")){
						$cortar=true;
					}*/
					
			//		echo "$$";
				}
				
				$this->_actual($actual);
				if ($error){
					foreach ($exitosos as $clave => $aliasex){
						$this->destruir_estructura($aliasex);
						//echo "jjiji2!";
					}
					//echo "hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh";
					return false;
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: en caso de exito queda construida la estructura definida en el string
			+--------------------------------------------
		<-----------------------Fin de _dato($dato, $camino=null) */

		/*FUNCION construir($string, $camino=null) ---------------------------------------->
				Interfaz: construir
			+--------------------------------------------
				Caso de uso: Contruye una estructura a partir de una cadena. Mas adelante se va a especificar bien la estructura q tiene q tener la cadena
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$string : la cadena con informacion del o que se va a construir
					$camino (opcional):  el camino por el cual avanzar antes de intentar construir
			+--------------------------------------------
				Notas: agregado en 1.6
			+--------------------------------------------
				Cuerpo:
				*/	
				public function construir($string, $camino=null){
				//	echo "+";
					//echo "BBBBBBBBB".$string."BBBBBBBBBBBBB";

					if (!is_string($string)){
						$this->_error("Iterador->construir(string) cadena debe ser un string!");
						return null;
					};

					$fin=strlen($string);
					
					if ($fin<1){
						$this->_error("Iterador->construir(string) la cadena es un string vacio");
						return null;
					}
					if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
						Iterador::_error("Iterador->construir(string) el iterador no esta ocupado");
						return null;
					}
					if (!$origen=$cuerpo->adyacente("actual")){
						$this->_error("Iterador->construir(string) el iterador no tiene niguna asignado nodo actual");
						return null;
					}
					$actual=$origen;
					$avanzo=false;
					if (($camino)&&(!$avanzo=$this->avanzar_interno($camino))){
						$this->_error("Iterador->construir(string) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
						return null;
					}
				//	$actual=$origen;
					//creo cadena de nodos DATOS AUXILIARES
					
					//$icad=Iterador::iterador("Iterador->construir");
					//$ires=Iterador::iterador("auxiliar2");
				/*	$cad=null;
					$cadena=null;
					
					for ($i=0; $i<$fin; $i++){
						$nodo=Nodo::crear_con_dato($string{$i});
						if ($i==0){
							$cadena=$nodo;
							$cad=$cadena;
							$primera=$cadena;
							//$icad->_actual($nodo);
						}else{
							$cad->_adyacente_en($nodo, "sig");
							$cad=$cad->adyacente("sig");
						}
					}
						*/
					//Comienzo algoritmo principal
					//echo "a";
			//		echo "*";
					//le paso la cadena de nodos
					//$icad->_actual($cadena);

					//$ires->_actual($this->actual());
					$i=0;
					$mal=false;
					if (!$this->construir_enlaces($string, $i, $fin)){
						//$this->_error("Iterador->construir() error de sintaxis (0)");
						$mal=true;
					}
					
					
					/*if ($cadena->adyacente("sig")){
						$mal=true;		
					}*/
					if ($i<$fin){
						$mal=true;		
					}
					
					//$icad->_actual($cadena);
					/*$cadena=$primera;
					$ante=$primera;
					while ($cadena=$cadena->adyacente("sig")){
						Nodo::eliminar($ante);
						$ante=$cadena;
					}
					//$icad->liberar();
					//$icad->desocupar();
					Nodo::eliminar($ante);*/
					
					$cuerpo->_adyacente_en($origen, "actual");
					
					if ($mal){
						$this->_error("Iterador->construir() error de sintaxis");
						return false;
					}

					return true;
				}
			/*-------------------------------------------
				Datos de salida: true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones: en caso de exito queda construida la estructura definida en el string
			+--------------------------------------------
		<-----------------------Fin de construir($string, $camino=null) */

		/*FUNCION destruir_hijos_arboleado($iarbol) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: Destruye los hijos de un arboleado
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$iarbol : el iterador arbol posicionado en el lugar en donde se desea destruir los "hijos"
			+--------------------------------------------
				Notas: privada auxiliar
			+--------------------------------------------
				Cuerpo:
				*/
			private function destruir_hijos_arboleado($iarbol){
				$padre=$iarbol->actual();
				while ($padre->adyacente("hmi")){
					//$actual=$iarbol->actual();
					$elim=$iarbol->eliminar_hmi();
					//$this->destruir_nodo_arboleado($iarbol->eliminar_hmi(),$iarbol->actual());

					//$actual=$this->actual();
					$nodoelim=$elim->adyacente("nodo");
					$padreelim=$padre->adyacente("nodo");

					$enlaceelim=$elim->dato();

				//	echo "KKKK".$enlaceelim."k";
					Nodo::eliminar($elim);
					$padreelim->eliminar_enlace($this->enlace($enlaceelim));
					Nodo::eliminar($nodoelim);
					/*
					//if ($padreelim){
						$this->_actual($padreelim);
						$this->eliminar_adyacente($enlaceelim);
					//}
					Nodo::eliminar($nodoelim);
					$this->_actual($actual);*/
				}
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: quedan destruidos los hijos
			+--------------------------------------------
		<-----------------------Fin es_arboleable($alias, $semaforos=null)*/	

	/*private  function destruir_nodo_arboleado($nodo, $padre){

		///if ($padre){
		$actual=$this->actual();
		$nodoelim=$nodo->adyacente("nodo");
		$padreelim=$padre->adyacente("nodo");
		$enlaceelim=$nodo->dato();
	//	echo "KKKK".$enlaceelim."k";
		Nodo::eliminar($nodo);
		if ($padreelim){
			$this->_actual($padreelim);
			$this->eliminar_adyacente($enlaceelim);
		}
		Nodo::eliminar($nodoelim);
		$this->_actual($actual);
	}*/
		/*FUNCION destruir_arboleado($iarbol) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: Destruye un arboleado junto con la estrucura real a la que se�ala
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$iarbol : el iterador Arbol cuya raiz es la raiz del arboleado que se desea destruir
					$camino (opcional): el camino que debe recorrer en la estructura original para llegar al lugar donde debe destruir.
			+--------------------------------------------
				Notas: privada auxiliar
			+--------------------------------------------
				Cuerpo:
				*/
			private function destruir_arboleado($iarbol, $camino=null){
				//$posini=$this->actual();
				$raiz=$iarbol->raiz();
				$idraiz=$raiz->id();

				$fin=false;
				while (!$fin){
					//echo "*1*";
					//echo "%".$this->actual()->dato()."%";
					//$constructor->abre($this);
					if (!$iarbol->hmi()) {
					//	echo "*4*";
						//$constructor->cierra($this);
						$this->destruir_hijos_arboleado($iarbol);
						//$this->destruir_hijos();
						if (!$iarbol->hd()){
						//	echo "*5*";
							$fin1=false;
							while ((!$fin)&&(!$fin1)){
								if ($iarbol->p()){
									//$constructor->cierra($this);
									$this->destruir_hijos_arboleado($iarbol);
									if($iarbol->hd()){
										$fin1=true;
									}
								}else{
								//	echo "llego a la raiz??";

									$this->destruir_hijos_arboleado($iarbol);
									$fin1=true;
								}
							}
						}	
					}
					
					//echo "*6*";
					$fin=$iarbol->actual()->id()==$idraiz;
				/*	if ($fin){
						$constructor->cierra($this);
					}*/
				}			
				//echo "*7*";
				//$raiz=$iarbol->raiz();
				$iarbol->liberar();
				$cuerpo=$this->raiz_cuerpo;
				$origen=$cuerpo->adyacente("actual");
				$actual=null;
				$avanzo=false;
				if ($camino){
					//$actual=$cuerpo->
					$this->avanzar_interno($camino);
					$avanzo=true;
				}

				$actual=$cuerpo->adyacente("actual");
				$actual->eliminar_enlace($this->enlace($raiz->dato()));
				$ult=$raiz->eliminar_enlace("nodo");
				Nodo::eliminar($ult);
				$iarbol->liberar();
				Nodo::eliminar($raiz);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				//$this->destruir_nodo_arboleado($raiz, $posini);
				//$this->_actual($posini);
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: queda destruida el arbol y la estructura
			+--------------------------------------------
		<-----------------------Fin destruir_arboleado($iarbol)*/	

		/*FUNCION destruir_estructura($alias, $semaforos=null, $camino=null) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: Destruye una estructura a partir de un enlace
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias : el enlace a partir del cual se desea destruir la estrucura
					$semaforos (opcional): una cadena de alias separa por ; que indican que enlaces no se deben seguir en el proceso de dstruccion
					$camino (opcional): el camino a avanzar antes de realizar la operacion. 
			+--------------------------------------------
				Notas: //----V1.7.2--------------------//
					//La contraria de construir. Ningun nodo de la estructura a destruir puede estar referenciado dos veces, ni estar dentro de un bucle de enlaces de ninguna manera.
			+--------------------------------------------
				Cuerpo:
				*/
			public function destruir_estructura($alias, $semaforos=null, $camino=null){
			/*	if (!$this->ocupado()){
					$this->_error("Iterador->destruir_estructura() el Iterador no est� ocupado!!");
					return null;		
				}*/
				if($arbol=$this->arbolear($alias,$semaforos, $camino)){
					$iarbol=Arbol::iterador("Iterador->destruir");
					$iarbol->_raiz($arbol);
					$this->destruir_arboleado($iarbol, $camino);
					//$iarbol->liberar();
					$iarbol->desocupar();

					//$nodo=$this->eliminar_adyacente($alias);
					//Nodo::eliminar($nodo);
					
					return true;
				}else{
					$this->_error("Iterador->destruir_estructura() no se pudo destruir, vea errores de la funcion arbolear para mas informacion");
					return false;
				}		
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: queda destruida la estructura
			+--------------------------------------------
		<-----------------------Fin destruir_estructura($alias, $semaforos=null, $camino=null)*/	

//----------copiar_y_destruir---------------------------------------------------------------//	
		/*FUNCION copiar_y_destruir($alias,$semaforos=null, $camino=null) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: Copia y destruye la estructura
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias : el enlace a partir del cual se desea copiar y destruir la estrucura
					$semaforos(opcional) : una cadena de alias separa por ; que indican que enlaces no se deben seguir en el proceso de copiado y dstruccion
					$camino(opcional): el camino a seguir antes de realizar la operacion
			+--------------------------------------------
				Notas: ----V1.7.3--------------------------------------------------------------------------------
					//Crea una cadena a partir de la estructura adyacente en el enlace pasado por parametro. La misma puede ser utilizada para en la funcion construir en otro lugar de la estuctura y asi replicarla. La estructura original queda destruida. Viene a ser un "cortar" de las funciones de windows
			+--------------------------------------------
				Cuerpo:
				*/	
			public function copiar_y_destruir($alias,$semaforos=null, $camino=null){
			/*	if (!$this->ocupado()){
					$this->_error("Iterador->copiar_y_destruir() el Iterador no est� ocupado!!");
					return null;		
				}*/
				if($arbol=$this->arbolear($alias, $semaforos, $camino)){
					$iarbol=Arbol::iterador("Iterador->copiar_y_destruir");
					$iarbol->_raiz($arbol);
					$res=$this->copiar_y_destruir_arboleado($iarbol, $camino);
					//$nodo=$this->eliminar_adyacente($alias);
					//Nodo::eliminar($nodo);
					//$iarbol->liberar();
					$iarbol->desocupar();
					//return $alias."=".$res;
					return $res;
				}else{
					$this->_error("Iterador->copiar_y_destruir() no se puede copiar vea errores de la funcion arbolear para mas informacion");
					return null;
				}		
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: queda destruida la estructura
			+--------------------------------------------
		<-----------------------Fin copiar_y_destruir($alias,$semaforos=null)*/	
		
		/*FUNCION copiar_cierra($actual) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia el "cierre" auxiliar de copiar y destruir
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$actual :  
			+--------------------------------------------
				Notas: ----V1.7.3--------------------------------------------------------------------------------
			+--------------------------------------------
				Cuerpo:
				*/
			private function copiar_cierra($actual){
				//$actual=$this->actual();
				$res="";
				if ($actual->adyacente("hmi")){
					$res=");";
				}else{
					$res=";";
				}
				//$this->_actual($this);
				return $res;		
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el char que corresponde al cierre
			+--------------------------------------------
		<-----------------------Fin copiar_cierra($actual)*/	
		
		/*FUNCION copiar_cierra($actual) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia el "abre" auxiliar de copiar y destruir
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$actual :  
			+--------------------------------------------
				Notas: ----V1.7.3--------------------------------------------------------------------------------
			+--------------------------------------------
				Cuerpo:
				*/
			private function copiar_abre($actual){
				//$actual=$this->actual();
				$res="";
				//$actual=$iarbol->actual();
				$dato=$actual->adyacente("nodo")->dato();
				if ($actual->adyacente("hmi")){
					//modificado 1.8.9
					$res=$res."(".$this->construir_escapar($dato).":";
				}else{
					//$iarbol->actual()->imprimir();
					/*if (!$actual->adyacente("nodo")){
						//echo "que mierda paso";
					}*/
					//modificado 1.8.9
					$res=$this->construir_escapar($dato);
				}
			//	$this->_actual($this);
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el string que corresponde al abre
			+--------------------------------------------
		<-----------------------Fin copiar_abre($iarbol)*/

		/*FUNCION copiar_y_destruir_arboleado($iarbol) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia el "abre" auxiliar de copiar y destruir
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$iarbol :  
			+--------------------------------------------
				Notas: ----V1.7.3--------------------------------------------------------------------------------
			+--------------------------------------------
				Cuerpo:
				*/
			private function copiar_y_destruir_arboleado($iarbol, $camino=null){
				//echo "RRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR";
				//$posini=$this->actual();
				$actual=$raiz=$iarbol->raiz();
				$idraiz=$actual->id();
				$res="";
				$fin=false;
				
				while (!$fin){
					//echo "*1*";
					//echo "%".$this->actual()->dato()."%";
					$res=$res.$this->construir_escapar($actual->dato())."=".$this->copiar_abre($actual);
					if (!$iarbol->hmi()) {
					//	echo "*4*";
						$res=$res.$this->copiar_cierra($actual);
						//$this->destruir_hijos();
						if (!$iarbol->hd()){
						//	echo "*5*";
							$fin1=false;
							while ((!$fin)&&(!$fin1)){
								if ($iarbol->p()){
									//$actual=$iarbol->actual();
									$res=$res.$this->copiar_cierra($actual=$iarbol->actual());
									$this->destruir_hijos_arboleado($iarbol);
									if($iarbol->hd()){
										//$actual=$iarbol->actual();
										$fin1=true;
									}
								}else{
								//	echo "llego a la raiz??";
									$fin1=true;
								}
							}
						}else{
							//$actual=$iarbol->actual();
						}	
					}else{
						//$actual=$iarbol->actual();
					}
					
					//echo "*6*";
					$actual=$iarbol->actual();
					$fin=$actual->id()==$idraiz;
				/*	if ($fin){
						$constructor->cierra($this);
					}*/
				}			
				//echo "*7*";
				//$raiz=$iarbol->raiz();
				//$iarbol->liberar();
				//$this->destruir_nodo_arboleado($raiz, $posini);
				//$this->elminar_adyacente(
				//Nodo::eliminar($raiz);
				$cuerpo=$this->raiz_cuerpo;
				$aux=$origen=$cuerpo->adyacente("actual");
				$avanzo=false;
				if ($camino){
					//$actual=$cuerpo->
					$avanzo=$this->avanzar_interno($camino);
					$aux=$cuerpo->adyacente("actual");
				}
				$aux->eliminar_enlace($this->enlace($raiz->dato()));
				$ult=$raiz->eliminar_enlace("nodo");
				Nodo::eliminar($ult);
				$iarbol->liberar();
				Nodo::eliminar($raiz);
				//$this->_actual($posini);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el string que corresponde al abre
			+--------------------------------------------
		<-----------------------Fin copiar_y_destruir_arboleado($iarbol)*/

//----------copiar---------------------------------------------------------------------//	
		/*FUNCION  copiar_estructura($alias, $semaforos=null,$camino=null) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia la estructura a partir de un enlace
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias del enlace desde el cual se va a iniciar la copia
					$semaforos (opcional): susecion de alias separados por punto y coma. Estos alias son los que no se van a seguir durante el proceso de copia
					$camino(opcional): el camino a seguir antes de realizar la operacion
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/
			public function copiar_estructura($alias, $semaforos=null,$camino=null){
			/*	if (!$this->ocupado()){
					$this->_error("Iterador->copiar_estructura() el Iterador no est� ocupado!!");
					return null;		
				}*/
				if($arbol=$this->arbolear($alias, $semaforos, $camino)){
					$iarbol=Arbol::iterador("Iterador->copiar_estructura($alias, $semaforos=null,$camino=null)");
					$iarbol->_raiz($arbol);
					$res=$this->copiar_arboleado($iarbol);
					$iarbol->desocupar();
					//$nodo=$this->eliminar_adyacente($alias);
					//Nodo::eliminar($nodo);
					//return $alias."=".$res;
					//$iarbol->destruir_arbol();
					return $res;
				}else{
					$this->_error("Iterador->copiar_estructura($alias, $semaforos=null,$camino=null) no se pudo copiar, vea errores de la funcion arbolear para mas informacion");
					return null;
				}		
			}
			/*-------------------------------------------
				Datos de salida: un string que es la "copia" de la estructura si tubo exito. null en caso contrario
			+--------------------------------------------
				Poscondiciones: el string que corresponde al abre
			+--------------------------------------------
		<-----------------------Fin copiar_estructura($alias, $semaforos=null,$camino=null)*/	

		/*FUNCION copiar_y_destruir_arboleado($iarbol) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia la estructura a partir de un enlace
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$alias: el alias del enlace desde el cual se va a iniciar la copia
					$semaforos (opcional): susecion de alias separados por punto y coma. Estos alias son los que no se van a seguir durante el proceso de copia
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/
	/*private function destruir_hijos_arboleado_copiar($iarbol){
		while ($iarbol->adyacente("hmi")){
			//$actual=$iarbol->actual();
			Nodo::eliminar($iarbol->eliminar_hmi());
		}
	}*/
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el string que corresponde al abre
			+--------------------------------------------
		<-----------------------Fin copiar_estructura($alias, $semaforos=null)*/

		/*FUNCION copiar_arboleado($iarbol) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: copia arboleado, auxiliar de copiar_estructura
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
					$iarbol: 
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/
			private function copiar_arboleado($iarbol){
				//echo "RRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR";
				//$posini=$this->actual();
				$raiz=$actual=$iarbol->raiz();
				$idraiz=$raiz->id();
				$res="";
				$fin=false;
				while (!$fin){
					//echo "*1*";
					//echo "%".$this->actual()->dato()."%";
					//modificado en 1.8.9
					$res=$res.$this->construir_escapar($actual->dato())."=".$this->copiar_abre($actual);
					if (!$iarbol->hmi()) {
					//	echo "*4*";
						$res=$res.$this->copiar_cierra($actual);
						//$this->destruir_hijos();
						if (!$iarbol->hd()){
						//	echo "*5*";
							$fin1=false;
							while ((!$fin)&&(!$fin1)){
								if ($iarbol->p()){
									//$actual=$iarbol->actual();
									$res=$res.$this->copiar_cierra($iarbol->actual());
									while ($iarbol->adyacente("hmi")){
										//$actual=$iarbol->actual();
										Nodo::eliminar($iarbol->eliminar_hmi());
									}
									if($iarbol->hd()){
										//$actual=$iarbol->actual();
										$fin1=true;
									}
								}else{
								//	echo "llego a la raiz??";
									$fin1=true;
								}
							}
						}else{
							//$actual=$iarbol->actual();
						}	
					}else{
						//$actual=$iarbol->actual();
					}
					$actual=$iarbol->actual();
					//echo "*6*";
					$fin=$actual->id()==$idraiz;
				/*	if ($fin){
						$constructor->cierra($this);
					}*/
				}			
				//echo "*7*";
				//$raiz=$iarbol->raiz();
				$iarbol->liberar();
				Nodo::eliminar($raiz);
				//$this->destruir_nodo_arboleado($raiz, $posini);
				//$this->_actual($posini);
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: el string que es la copia a partir del arboleado
			+--------------------------------------------
		<-----------------------Fin copiar_arboleado($iarbol)*/	

//---V1.7.4 destruir_estructuras_adyacentes()----------------------------------------------------------
		/*FUNCION destruir_estructuras_adyacentes($camino=null) ---------------------------------------->
				Interfaz: Estructura
			+--------------------------------------------
				Caso de uso: intenta destruir todas las estructuras adyacentes a un nodo
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas: Puede que alguna o todas las estructuras no sean destruidas en el caso de poseer algun bucle
			+--------------------------------------------
				Cuerpo:
				*/
			public function destruir_estructuras_adyacentes($camino=null){
				if ((!$cuerpo=$this->raiz_cuerpo) or (!$origen=$cuerpo->adyacente("actual"))){
					$this->_error("Iterador-> destruir_estructuras_adyacentes($camino=null) el iterador no tiene posici�n actual");
					return null;
				}
				$avanzo=false;
				
				if (($camino!==null)&&(!$avanzo=$this->avanzar_interno($camino))){
					$this->_error("Iterador-> destruir_estructuras_adyacentes($camino=null) posiblemente el camino pasado como parametro no sea valido tenga un error de sintaxis o no existan esos enlaces en la estructura. Ver las alertas para m�s informaci�n.");
					return null;
				}
				$funcion=function ($nodo, $enlace, $iterador){
				//	echo "<br>el indice q quiso destruir es ".$nodo->id()."+".$enlace;
					$iterador->destruir_estructura($enlace);
					
					//$iterador->eliminar
				};
				$cuerpo->adyacente("actual")->por_cada_adyacente_ejecutar($funcion, $this);
				if ($avanzo){
					$cuerpo->_adyacente_en($origen, "actual");
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
			+--------------------------------------------
				Poscondiciones: true si realiza el proceso de destruccion, que no siempre puede resultar exitoso, o resultar parcial. null en caso de que no pueda realizar el proceso.
			+--------------------------------------------
		<-----------------------Fin destruir_estructuras_adyacentes($camino=null)*/	



		
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- CLONACION V1.8.3 ///////////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//-----------------clonar!--------------------------------------
		
		/*FUNCION  fue_clonado() ---------------------------------------->
				Interfaz: CLONACION
			+--------------------------------------------
				Caso de uso: verifica si fue clonado
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/	
			public function fue_clonado(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::fue_clonado() el iterador no esta ocupado");
					return false;
				}	
				if ($cuerpo->adyacente("cantidad de clones")){
					return true;
				}else{
					return false;
				}
			}
			/*-------------------------------------------
				Datos de salida: true si fueron creados clones a partir de el. false en caso contrario. null si hay error
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin fue_clonado()*/	
		/*FUNCION  es_clon() ---------------------------------------->
				Interfaz: CLONACION
			+--------------------------------------------
				Caso de uso: verifica si es un clon
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/			
			public function es_clon(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::es_clon() el iterador no esta ocupado");
					return false;
				}	
				if ($cuerpo->adyacente("clon")){
					return true;
				}else{
					return false;
				}
			}
			/*-------------------------------------------
				Datos de salida: true si es un clon de otro iterador. false en caso contrario. null si hubo error
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin es_clon()*/	
		/*FUNCION  sumar_clon() ---------------------------------------->
				Interfaz: CLONACION
			+--------------------------------------------
				Caso de uso: suma un clon. auxiliar
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas: 
			+--------------------------------------------
				Cuerpo:
				*/		
		/*	private function sumar_clon(){
				if (!$ncant=$this->raiz_cuerpo->adyacente("cantidad de clones")){
					$this->raiz_cuerpo->_adyacente_en($ncant=Nodo::crear_con_dato(0),"cantidad de clones");
				}
				$cant=$ncant->dato();
				$cant++;
				$ncant->_dato($cant);
				return $cant;
			}*/
			/*-------------------------------------------
				Datos de salida: devuelve la cantidad de clones
			+--------------------------------------------
				Poscondiciones: queda sumado un clon
			+--------------------------------------------
		<-----------------------Fin sumar_clon()*/	

		/*FUNCION  clonar_estatico($iterador) ---------------------------------------->
				Interfaz: CLONACION
			+--------------------------------------------
				Caso de uso: clonar cuerpo y registrar clon 
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$iterador: el iterador que se desea clonar
			+--------------------------------------------
				Notas: 
					//esta funcion debera redefinirse en las clases herederas si se agreagan nuevos elementos al cuerpo del iterador
			+--------------------------------------------
				Cuerpo:
				*/
			protected static function clonar_estatico($iterador){
				if (!($iterador instanceof Iterador)){
					Iterador::_error("Iterador::clonar_estatico($iterador) el iterador de entrada tiene que ser de la clase Iterador o de una clase heredera de la misma");
					return null;
				}
				if ((!$cuerpo=$iterador->raiz_cuerpo) or (!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") el iterador pasado por parametro no esta ocupado");
					return null;
				}
				//recuperar nodo iteradores
				if(!$iteradores=Nodo::nodo_por_id("iteradores")){
					//$iteradores->imprimir();
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") no existen iteradores");
					return null;
				}
				//recupero el nodo clase
				$nombrec=get_class($iterador);
				if(!$nclase=$iteradores->adyacente($nombrec)){
					//$iteradores->imprimir();
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") no existen iteradores de esa clase");
					return null;
				}
				//recupero el nodo iteradores
				if(!$nits=$nclase->adyacente("iteradores")){
					//$iteradores->imprimir();
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") error interno en la estructura");
					return null;
				}
		
				//sumo a la varable//sino funciona tengo que desaser
				
				if (!$ncant=$cuerpo->adyacente("cantidad de clones")){
					$cuerpo->_adyacente_en($ncant=Nodo::crear_con_dato(0),"cantidad de clones");
				}
				$cant=$ncant->dato();
				$cant++;
				$ncant->_dato($cant);

				//creo el nombre
				$nombregen=$cuerpo->dato();
				$nombre_clon=$nombregen." - Clon ".$cant;

				//obtener el nodo "informacion compartida"
				$ninformacion=null;
				if(!$ninformacion=$nclase->adyacente("informacion compartida")){
					//$iteradores->imprimir();
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") error interno en la estructura (2)");
					$ncant->_dato($cant--);
					return null;
				}

				//obtengo el nodo q apunta a todos los iteradores
				$niteradores=null;
				if (!$niteradores=$nclase->adyacente("iteradores")){
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") error interno en la estructura (3)");
					$ncant->_dato($cant--);
					return null;
				}

				//verifico si no existe un iterador con ese nombre
				if ($niteradores->adyacente($nombre_clon)){
					Iterador::_error("Iterador::clonar_estatico(".$iterador.") ya existe un iterador con el nombre del clon!!");
					$ncant->_dato($cant--);
					return null;
				}
					//
				
				$niteradores->_adyacente_en($cuerpo_clon=Nodo::crear_con_dato($nombre_clon),$nombre_clon);
				$cuerpo_clon->_adyacente_en($ninformacion, "clase");

				$iaux=Iterador::iterador("Iterador::clonar->estatico");
				$iaux->_actual($cuerpo);

				//copio alias
				if ($cuerpo->adyacente("alias")){
					$copia=$iaux->copiar_estructura("alias");
					$iaux->_actual($cuerpo_clon);
					$iaux->construir($copia);
					$iaux->_actual($cuerpo);
					$copia=$iaux->copiar_estructura("enlaces alias");
					$iaux->_actual($cuerpo_clon);
					$iaux->construir($copia);
					$iaux->_actual($cuerpo);
				}
				//copio datos
				if ($cuerpo->adyacente("datos")){
					$copia=$iaux->copiar_estructura("datos");
					$iaux->_actual($cuerpo_clon);
					$iaux->construir($copia);
				}
				$iaux->liberar();
				$iaux->desocupar();
				//copio enlace actual
				if ($actual=$cuerpo->adyacente("actual")){
					$cuerpo_clon->_adyacente_en($actual, "actual");
				}

				//digo que es un clon

				$cuerpo_clon->_adyacente_en($cuerpo_clon,"clon");
				return $nombre_clon;
			}
			/*-------------------------------------------
				Datos de salida: devuelve el nombre del clon, null en caso contrario
			+--------------------------------------------
				Poscondiciones: queda un clon creado en la estructura de iteradores listo para ser cargado
			+--------------------------------------------
		<-----------------------Fin clonar_estatico($iterador)*/	
		/*FUNCION  clonar() ---------------------------------------->
				Interfaz: CLONACION
			+--------------------------------------------
				Caso de uso: clonar
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$iterador: el iterador que se desea clonar
			+--------------------------------------------
				Notas: 
					//esta funcion debera copiarse en todas als clases herederas para asegurarse de que se utilice el guardar de la ultima clase heredera.
			+--------------------------------------------
				Cuerpo:
				*/
	
			public function clonar(){
				//clonar estatico
				if ($nombre_clon=self::clonar_estatico($this)){
				//echo $nombre_clon."**";
					$iter= new Iterador;
					if (!Iterador::cargar_interno($nombre_clon, $iter)){
						Iterador::_error("Iterador->clonar() no se pudo cargar el clon");
						return null;
					}
					return $iter;
				}else{
					Iterador::_error("Iterador->clonar() no se pudo clonar");
					return null;				
				}

			}
			/*-------------------------------------------
				Datos de salida: devuelve el clon en el caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones: queda un clon creado 
			+--------------------------------------------
		<-----------------------Fin clonar()*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- DATOS V1.8.5 ///////////////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	//------V1.8.5----------------nodo con datos ----------------------------------------
	/*
		NOTAS DE LA INTERFAZ: La unica diferencia entre la interface DATOS y la interfaz DATOS INDIVIDUALES es que los datos individuales no son clonados en el caso de que se clone; al contrario de la interface DATOS que cualquier dato agregado con esta inrefaz seran copiados si se clona el iterador.
	*/
	//-------------interface datos-----------------------------------------------------------------------------------
		/*FUNCION  _datos($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS 
			+--------------------------------------------
				Caso de uso: agrega datos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$datos: una cadena con el formato de "contruir" para agregar a la estructura interna de los "datos"
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de construir los nuevos datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos. Para agregar datos se debe utilizar esta funcion que tiene una llamada interna al metodo construir(), por ello se le tiene q pasar una cadena con el formato "construir" que contenga los datos q se quieran agregar.
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _datos($datos, $ruta=null){
				
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::_datos(datos, ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos")){
					//sino existe lo crea
					$cuerpo->_adyacente_en($ndatos=Nodo::crear_con_dato("datos"),"datos");
				}
				$it=Iterador::iterador("Iterador->_datos", $ndatos);
				//el iterador va a modificar su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);
				//comprueba si existe ruta*/
				if ($ruta!=null){
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->_datos($datos, $ruta=null) la ruta donde quiere construir los nuevos datos no existe");
						return null;
					}
				}
				//construyo los datos
				if (!$it->construir($datos)){
					//sino puedo construir me salgo
					$it->liberar();
					$it->desocupar();
					$this->_error("Iterador->_datos($datos, $ruta=null)  no pudo construir los datos");
					return null;		
				}
				//echo "JJJJ";
				$it->liberar();
				$it->desocupar();
				//$this->_actual($actual);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true en caso de tener exito, null en caso contrario.
			+--------------------------------------------
				Poscondiciones: queda construido los datos internos agregados
			+--------------------------------------------
		<-----------------------Fin _datos($datos, $ruta=null)*/	
		/*FUNCION  datos($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS
			+--------------------------------------------
				Caso de uso: retorna datos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de retornar los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos. Para recuperar datos se debe utilizar esta funcion. Para acceder a distintos lugares de la estructura de datos se puede utilizar un "camino" con el formato entendinble por la funcion avanzar
			+--------------------------------------------
				Cuerpo:
				*/
			public function datos($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::datos(ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos")){
					//sino existe me salgo
					$this->_alerta("Iterador->datos(ruta=null) no existe ningun nodo con datos");
					return null;
				}
				/*//el iterador va a leer su propio cuerpo!!
				$actual=$this->actual();
				$this->_actual($ndatos);
				*/
				$it=Iterador::iterador("Iterador->datos", $ndatos);
				//verifico si existe ruta y si puedo ir
				if ($ruta!=null){
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->datos(ruta=null) no pudo avanzar por la ruta especificada");
						return null;
					}
				}
				//retorno el nodo
				$res=$it->actual();
				$it->liberar();
				$it->desocupar();
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin datos($ruta=null)*/	
		/*FUNCION  destruir_datos($ruta=null) ---------------------------------------->
				Interfaz: DATOS
			+--------------------------------------------
				Caso de uso: destruye datos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de destruir los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos. Para recuperar datos se debe utilizar esta funcion. Para acceder a distintos lugares de la estructura de datos se puede utilizar un "camino" con el formato entendinble por la funcion avanzar
			+--------------------------------------------
				Cuerpo:
				*/		
			public function destruir_datos($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::destruir_datos(ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos")){
					//sino existe me salgo
					$this->_alerta("Iterador->destruir_datos(ruta=null) no existe ningun nodo con datos");
					return false;
				}
				//el iterador va a leer su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);*/
				
				$it=Iterador::iterador("Iterador->destruir_datos", $ndatos);
				//obtengo ruta
				//$rutaexp;
				
				//$adestruirposta="datos";
				if ($ruta!=null){
					//echo "M";
				/*	if (!$rutaexp=explode(";",$ruta)){
						$this->_actual($actual);
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					$adestruir=$rutaexp[count($rutaexp)-1];
					//echo "N";
					$rutaruta=substr($ruta,0,0-strlen($adestruir));
					//echo "**_".$rutaruta."_****";*/
					$adestruir=	$aux=null;
					if (!$it->avanzar($ruta,-1, $aux, $adestruir)){
						//echo "B";
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					//compruebo que existe el nodo datos o el nodo en la ruta a destruir
					//sino existe me salgo
					if (!$it->adyacente($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos(ruta=null) no existe el enlace desde el que quiere destruir");
						return false;
					}
					//$adestruirposta=$adestruir;
					if (!$it->destruir_estructura($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo destruir");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
				}else{
					if (!$it->destruir_estructuras_adyacentes()){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo destruir (1)");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
					$cuerpo->eliminar_enlace("datos");
					Nodo::eliminar($ndatos);
				}
				//destruyo
				//sino destruyo me salgo

				/*$it->liberar();
				$it->desocupar();*/
				//$this->_actual($actual);
				return true;

			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin destruir_datos($ruta=null)*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- DATOS INDIVIDUALES V1.8.5 //////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	//------V1.8.5----------------nodo con datos ----------------------------------------
	/*
		NOTAS DE LA INTERFAZ: La unica diferencia entre la interface DATOS y la interfaz DATOS INDIVIDUALES es que los datos individuales no son clonados en el caso de que se clone; al contrario de la interface DATOS que cualquier dato agregado con esta inrefaz seran copiados si se clona el iterador.
	*/
	//-------------interface datos-----------------------------------------------------------------------------------
		/*FUNCION  _datos_individuales($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS INDIVIDUALES
			+--------------------------------------------
				Caso de uso: agrega datos individuales
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$datos: una cadena con el formato de "contruir" para agregar a la estructura interna de los "datos individuales"
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos individuales" antes de construir los nuevos datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos individuales" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos. Para agregar datos se debe utilizar esta funcion que tiene una llamada interna al metodo construir(), por ello se le tiene q pasar una cadena con el formato "construir" que contenga los datos q se quieran agregar.
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _datos_individuales($datos, $ruta=null){		
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::_datos_individuales(datos, ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;

				if (!$ndatos=$cuerpo->adyacente("datos individuales")){
					//sino existe lo crea
					$cuerpo->_adyacente_en($ndatos=Nodo::crear_con_dato("datos individuales"),"datos individuales");
				}
				$it=Iterador::iterador("Iterador->_datos_individuales", $ndatos);
				//el iterador va a modificar su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);
				//comprueba si existe ruta*/
				if ($ruta!=null){
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->_datos_individuales($datos, $ruta=null) la ruta donde quiere construir los nuevos datos no existe");
						return null;
					}
				}
				//construyo los datos
				if (!$it->construir($datos)){
					//sino puedo construir me salgo
					$it->liberar();
					$it->desocupar();
					$this->_error("Iterador->_datos_individuales($datos, $ruta=null)  no pudo construir los datos");
					return null;		
				}
				//echo "JJJJ";
				$it->liberar();
				$it->desocupar();
				//$this->_actual($actual);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true en caso de tener exito, null en caso contrario.
			+--------------------------------------------
				Poscondiciones: queda construido los datos internos agregados
			+--------------------------------------------
		<-----------------------Fin _datos_individuales($datos, $ruta=null)*/	
		/*FUNCION  datos_individuales($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS INDIVIDUALES
			+--------------------------------------------
				Caso de uso: retorna datos individuales
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos individuales" antes de retornar los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos individuales" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos. Para recuperar datos se debe utilizar esta funcion. Para acceder a distintos lugares de la estructura de datos individuales se puede utilizar un "camino" con el formato entendinble por la funcion avanzar
			+--------------------------------------------
				Cuerpo:
				*/
			public function datos_individuales($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::datos_individuales(ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos individuales")){
					//sino existe me salgo
					$this->_alerta("Iterador->datos_individuales(ruta=null) no existe ningun nodo con datos");
					return null;
				}
				/*//el iterador va a leer su propio cuerpo!!
				$actual=$this->actual();
				$this->_actual($ndatos);
				*/
				$it=Iterador::iterador("Iterador->datos_individuales", $ndatos);
				//verifico si existe ruta y si puedo ir
				if ($ruta!=null){
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->datos_individuales(ruta=null) no pudo avanzar por la ruta especificada");
						return null;
					}
				}
				//retorno el nodo
				$res=$it->actual();
				$it->liberar();
				$it->desocupar();
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin datos_individuales($ruta=null)*/	
		/*FUNCION  destruir_datos_individuales($ruta=null) ---------------------------------------->
				Interfaz: DATOS INDIVIDUALES
			+--------------------------------------------
				Caso de uso: destruye datos individuales
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos individuales" antes de destruir los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos individuales" que no desaparecen a momento de desocupar el iterador. Solo pueden desaparecer si se destruye el iterador o se eliminan con la funcion de destruir_datos_individuales. Para recuperar datos se debe utilizar esta funcion. Para acceder a distintos lugares de la estructura de datos individuales se puede utilizar un "camino" con el formato entendinble por la funcion avanzar
			+--------------------------------------------
				Cuerpo:
				*/		
			public function destruir_datos_individuales($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::destruir_datos_individuales(ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos individuales")){
					//sino existe me salgo
					$this->_alerta("Iterador->destruir_datos_individuales(ruta=null) no existe ningun nodo con datos");
					return false;
				}
				//el iterador va a leer su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);*/
				
				$it=Iterador::iterador("Iterador->destruir_datos_individuales", $ndatos);
				//obtengo ruta
				//$rutaexp;
				
				//$adestruirposta="datos";
				if ($ruta!=null){
					//echo "M";
				/*	if (!$rutaexp=explode(";",$ruta)){
						$this->_actual($actual);
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					$adestruir=$rutaexp[count($rutaexp)-1];
					//echo "N";
					$rutaruta=substr($ruta,0,0-strlen($adestruir));
					//echo "**_".$rutaruta."_****";*/
					$adestruir=null;
					$aux=null;
					if (!$it->avanzar($ruta,-1, $aux, $adestruir)){
						//echo "B";
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_individuales(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					//compruebo que existe el nodo datos o el nodo en la ruta a destruir
					//sino existe me salgo
					if (!$it->adyacente($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_individuales(ruta=null) no existe el enlace desde el que quiere destruir");
						return false;
					}
					//$adestruirposta=$adestruir;
					if (!$it->destruir_estructura($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_individuales(ruta=null) no pudo destruir");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
				}else{
					if (!$it->destruir_estructuras_adyacentes()){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_individuales(ruta=null) no pudo destruir (1)");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
					$cuerpo->eliminar_enlace("datos individuales");
					Nodo::eliminar($ndatos);
				}
				//destruyo
				//sino destruyo me salgo

				
				//$this->_actual($actual);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin destruir_datos_individuales($ruta=null)*/
	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- DATOS TEMPORALES V1.9.9 ////////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->

	//------V1.8.5----------------nodo con datos ----------------------------------------
	/*
		NOTAS DE LA INTERFAZ: Los datos temporales son eliminados cada vez q se desocupa el iterador y no son clonados
	*/
	//-------------interface datos-----------------------------------------------------------------------------------
		/*FUNCION  _datos_temporales($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS TEMPORALES 
			+--------------------------------------------
				Caso de uso: agrega datos temporales
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$datos: una cadena con el formato de "contruir" para agregar a la estructura interna de los "datos"
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de construir los nuevos datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos temporales" que son limpiados y (eliminados) al momento de desocupar el iterador. Para agregar datos se debe utilizar esta funcion que tiene una llamada interna al metodo construir(), por ello se le tiene q pasar una cadena con el formato "construir" que contenga los datos q se quieran agregar.
			+--------------------------------------------
				Cuerpo:
				*/	
			public function _datos_temporales($datos, $ruta=null){
				
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::_datos_temporales(datos, ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos temporales")){
					//sino existe lo crea
					$cuerpo->_adyacente_en($ndatos=Nodo::crear_con_dato("datos temporales"),"datos temporales");
				}
				$it=Iterador::iterador("Iterador->_datos_temporales", $ndatos);
				//el iterador va a modificar su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);
				//comprueba si existe ruta*/
				if ($ruta!=null){
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->_datos_temporales($datos, $ruta=null) la ruta donde quiere construir los nuevos datos no existe");
						return null;
					}
				}
				//construyo los datos
				if (!$it->construir($datos)){
					//sino puedo construir me salgo
					$it->liberar();
					$it->desocupar();
					$this->_error("Iterador->_datos_temporales($datos, $ruta=null)  no pudo construir los datos");
					return null;		
				}
				//echo "JJJJ";
				$it->liberar();
				$it->desocupar();
				//$this->_actual($actual);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: true en caso de tener exito, null en caso contrario.
			+--------------------------------------------
				Poscondiciones: queda construido los datos internos agregados
			+--------------------------------------------
		<-----------------------Fin _datos_temporales($datos, $ruta=null)*/	
		/*FUNCION  datos_temporales($datos, $ruta=null) ---------------------------------------->
				Interfaz: DATOS TEMPORALES
			+--------------------------------------------
				Caso de uso: retorna datos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de retornar los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos temporales" que son limpiados y (eliminados) al momento de desocupar el iterador. Para agregar datos se debe utilizar esta funcion que tiene una llamada interna al metodo construir(), por ello se le tiene q pasar una cadena con el formato "construir" que contenga los datos q se quieran agregar.
			+--------------------------------------------
				Cuerpo:
				*/
			public function datos_temporales($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::datos_temporales(ruta=null) el iterador no esta ocupado");
					return null;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos temporales")){
					//sino existe me salgo
					$this->_alerta("Iterador->datos_temporales(ruta=null) no existe ningun nodo con datos");
					return null;
				}
				/*//el iterador va a leer su propio cuerpo!!
				$actual=$this->actual();
				$this->_actual($ndatos);
				*/
				$it=Iterador::iterador("Iterador->datos_temporales", $ndatos);
				//verifico si existe ruta y si puedo ir
				if ($ruta!=null){
					//echo $ruta."HHHHHHHHHHHH";
					if (!$it->avanzar_interno($ruta)){
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->datos_temporales(ruta=null) no pudo avanzar por la ruta especificada");
						return null;
					}
				}
				//retorno el nodo
				$res=$it->actual();
				$it->liberar();
				$it->desocupar();
				return $res;
			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin datos_temporales($ruta=null)*/	
		/*FUNCION  destruir_datos_temporales($ruta=null) ---------------------------------------->
				Interfaz: DATOS TEMPORALES
			+--------------------------------------------
				Caso de uso: destruye datos
			+--------------------------------------------
				Precondiciones:
			+--------------------------------------------
				Datos de entrada: 
					$ruta (opcional): el camino a seguir dentro de la estructura de "datos" antes de destruir los datos agregados
			+--------------------------------------------
				Notas: 
					Cada iterador contiene un nodo con "datos temporales" que son limpiados y (eliminados) al momento de desocupar el iterador. Para agregar datos se debe utilizar esta funcion que tiene una llamada interna al metodo construir(), por ello se le tiene q pasar una cadena con el formato "construir" que contenga los datos q se quieran agregar.
			+--------------------------------------------
				Cuerpo:
				*/		
			public function destruir_datos_temporales($ruta=null){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::destruir_datos_temporales(ruta=null) el iterador no esta ocupado");
					return false;
				}	
				$ndatos=null;
				if (!$ndatos=$cuerpo->adyacente("datos temporales")){
					//sino existe me salgo
					$this->_alerta("Iterador->destruir_datos_temporales(ruta=null) no existe ningun nodo con datos");
					return false;
				}
				//el iterador va a leer su propio cuerpo!!
				/*$actual=$this->actual();
				$this->_actual($ndatos);*/
				
				$it=Iterador::iterador("Iterador->destruir_datos_temporales", $ndatos);
				//obtengo ruta
				//$rutaexp;
				$cuerpo->eliminar_enlace("guardar recorrido");
				//$adestruirposta="datos";
				if ($ruta!==null){
					//echo "M";
				/*	if (!$rutaexp=explode(";",$ruta)){
						$this->_actual($actual);
						$this->_error("Iterador->destruir_datos(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					$adestruir=$rutaexp[count($rutaexp)-1];
					//echo "N";
					$rutaruta=substr($ruta,0,0-strlen($adestruir));
					//echo "**_".$rutaruta."_****";*/
					$adestruir=null;
					$aux=null;
					if (!$it->avanzar($ruta,-1, $aux, $adestruir)){
						//echo "B";
						//sino puedo ir a la ruta m e salgo
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_temporales(ruta=null) no pudo avanzar por la ruta especificada");
						return false;
					}
					//compruebo que existe el nodo datos o el nodo en la ruta a destruir
					//sino existe me salgo
					if (!$it->adyacente($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_temporales(ruta=null) no existe el enlace desde el que quiere destruir");
						return false;
					}
					//$adestruirposta=$adestruir;
					if (!$it->destruir_estructura($adestruir)){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_temporales(ruta=null) no pudo destruir");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
				}else{
					//echo "Perrrrrrrrequee";
					if (($nvisitados=$ndatos->adyacente("visitados"))and ($nlista=$nvisitados->adyacente("lista de recorridos"))){
						//echo "sapata";
						$this->destruir_visitados_guardados($nvisitados);
					}
					/*} and $nlista=$nvisitados->adyacente("lista de recorridos")){
						"alalalapppa";
						$this->destruir_visitados_guardados($nlista);
					}
*/				
					if (!$it->destruir_estructuras_adyacentes()){
						//$this->_actual($actual);
						$it->liberar();
						$it->desocupar();
						$this->_error("Iterador->destruir_datos_temporales(ruta=null) no pudo destruir (1)");
						return false;		
					}
					$it->liberar();
					$it->desocupar();
					//$cuerpo->eliminar_enlace("datos temporales");
					Nodo::eliminar($ndatos);
				}
				//destruyo
				//sino destruyo me salgo

				/*$it->liberar();
				$it->desocupar();*/
				//$this->_actual($actual);
				return true;

			}
			/*-------------------------------------------
				Datos de salida: retorna el nodo datos, o el nodo al final del camino si se le paso una ruta por parametro. Null si no tuvo exito
			+--------------------------------------------
				Poscondiciones: 
			+--------------------------------------------
		<-----------------------Fin destruir_datos_temporales($ruta=null)*/

	//********************************************************************************
	//------------------------------------------------------------------------------->
	//---------------------- VISITADOS v2.0.1 //////// //////////////////////////////>
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
	//------------------------------------------------------------------------------->
		/*private FUNCION guardar_visitado_interno() ------------------------------------------->
				Interfaz: Visitados 
			+--------------------------------------------
				Caso de uso: guarda la posicion actual
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$ndatos: tiene que ser el nodo con los "datos temporales"
					$nvisitado: es el nodo actual que se desea guardara
			+--------------------------------------------
				Notas:
					elimina cualquier posicion siguiente que exista
			+--------------------------------------------
				Cuerpo:
			*/			
			protected function guardar_visitado_interno($ndatos, $nvisitado){
				if (!$nlista=$ndatos->adyacente("lista de recorridos")){
					$ndatos->_adyacente_en($nlista=Nodo::nodo(), "lista de recorridos");
				}
				//es el primero en guardar
				if (!$ultimo=$nlista->adyacente("ultimo")){
					$nlista->_adyacente_en($ultimo=Nodo::nodo((string)1),"ultimo");
					$ultimo->_adyacente_en($nvisitado, "referencia");
					$nlista->_adyacente_en($ultimo, "primero");
				//	echo "catanta";
				}else{//es el segunto o mas
					$cant=(int)$ultimo->dato();
					//echo "cant".$cant;
					$cant++;
					$aux=Nodo::nodo((string)$cant);
					$aux->_adyacente_en($nvisitado, "referencia");
					$aux->_adyacente_en($ultimo, "anterior");
					$ndestruir=$ultimo->adyacente("siguiente");
					$ultimo->_adyacente_en($aux, "siguiente");
					$nlista->_adyacente_en($aux, "ultimo");
					while ($ndestruir){
						$ndestruirs=$ndestruir->adyacente("siguiente");
						if ($ndestruirs){
							$ndestruirs->eliminar_enlace("anterior");
						}
						Nodo::eliminar($ndestruir);
						$ndestruir=$ndestruirs;
					}
					//$ultimo=$aux;
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
					True
			+--------------------------------------------
				Poscondiciones:
					queda "guardada" la posicon actual
			+--------------------------------------------
		<-----------------------Fin de guardar_visitado_interno()*/

		/*FUNCION activar_guardar_visitados() ------------------------------------------->
				Interfaz: Visitados
			+--------------------------------------------
				Caso de uso: activa "guardar_visitados"
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:

			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/		
		
			public function activar_guardar_visitados(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					self::_error("Arbol->activar_guardar_visitados() el iterador no esta ocupado");
					return false;
				}
				//busca el nodo
				//si no existe lo crea
	/*			$nlista=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
					/*=null;visitados_auxiliar_crear_obtener_lista($cuerpo,$crear=true)
				if (!$ndatos=$this->datos_temporales("buscar")){
					//echo "no tenia datos temporales";
					$this->_datos_temporales("buscar=");
					$ndatos=$this->datos_temporales("buscar");
				}*/
				//busco el enlace
				//si no existe lo creo
				if (!$cuerpo->adyacente("guardar recorrido")){
					$cuerpo->_adyacente_en($cuerpo, "guardar recorrido");
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
					True si tuvo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones:
					queda activada la opcion "guardar_visitados"
			+--------------------------------------------
		<-----------------------Fin de activar_guardar_visitados()*/

		/*FUNCION desactivar_guardar_visitados_() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso: desactiva "guardar_visitados_en_la_busqueda"
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:

			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/			
			function desactivar_guardar_visitados(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					self::_error("Arbol->desactivar_guardar_visitados() el iterador no esta ocupado");
					return false;
				}
				//busca el nodo
				//si no existe lo crea
		/*		$nlista=null;
				if (!$nlista=$this->visitados_auxiliar_crear_obtener_lista($cuerpo, false)){
					//echo "no tenia datos temporales";
					return true;
				}

				//busco el enlace
				if (!$cuerpo->adyacente("guardar recorrido")){
					return true;
				}*/
				$cuerpo->eliminar_enlace("guardar recorrido");
				return true;				
			}
			/*-------------------------------------------
				Datos de salida: 
					True
			+--------------------------------------------
				Poscondiciones:
					queda desactivada la opcion "guardar_visitados_en_la_busqueda"
			+--------------------------------------------
		<-----------------------Fin de desactivar_guardar_visitados()*/

		/*FUNCION destruir_resultado_de_busqueda($resultado) ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso: destruye una estructura originada en el llamado a la funcion "buscar_todos()"
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$ndatos: el nodo con los datos temporales
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/	
			private function destruir_visitados_guardados($ndatos){
				//echo "papa";
				if (!$nlista=$ndatos->adyacente("lista de recorridos")){
					//$ndatos->imprimir();
					return null;
				}
				//$nlista->imprimir();
				//$nlista=
				//echo "<br/>entroZ";
				if (!($nlista instanceof Nodo)){
					Iterador::_error("Arbol::destruir_resultado_de_busqueda(ndatos) el ndatos tiene que ser un nodo");
					return false;
				}
				$ultimo=$nlista->adyacente("ultimo");
				$primero=$nlista->adyacente("primero");
				
				if (!$ultimo or !$primero){
					Iterador::_error("Arbol::destruir_resultado_de_busqueda(ndatos) el ndatos no tiene la estructura correcta");
					return false;
				}
				$eliminar=true;
				//echo "entroZ2";
				While ($eliminar){
					//echo "entroZ3";
					if ($ultimo->dato()==1){
						//echo "entroZ4";
						$nlista->eliminar_enlace("ultimo");
						$nlista->eliminar_enlace("primero");
						Nodo::eliminar($ultimo);
						Nodo::eliminar($nlista);
						$eliminar=false;
					}else{
						//echo "entro5";
						if (!$anterior=$ultimo->adyacente("anterior")){
							//echo "entro6";
							Iterador::_error("Arbol::destruir_resultado_de_busqueda(ndatos) error interno en la estructura del ndatos");
							return false;
						};
						$nlista->_adyacente_en($anterior, "ultimo");
						$anterior->eliminar_enlace("siguiente");
						Nodo::eliminar($ultimo);
						$ultimo=$anterior;

					}
				}
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
					true si tubo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones:
					queda destruida la estructura
			+--------------------------------------------
		<-----------------------Fin de b destruir_resultado_de_busqueda($resultado)*/
		/* private FUNCION visitados_auxiliar_crear_obtener_lista($cuerpo,$crear=true) ------------------------------------------->
				Interfaz: Visitados 
			+--------------------------------------------
				Caso de uso: auxiliar, obtiene o crea el nodo de datos temoporales de buscar
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
					$crear=true: indica si debe crearlo o no en el caso de que no exista
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			protected function visitados_auxiliar_crear_obtener_lista($cuerpo,$crear=true){
				$ndatos=null;
				if (!$ndatost=$cuerpo->adyacente("datos temporales")){
					if (!$crear){
						return null;
					}
					$cuerpo->_adyacente_en($ndatost=Nodo::crear_con_dato("datos temporales"),"datos temporales");
					$ndatost->_adyacente_en($ndatos=Nodo::crear(), "visitados");
				}

				if (!$ndatos and !$ndatos=$ndatost->adyacente("visitados")){
					if (!$crear){
						return null;
					}
					$ndatost->_adyacente_en($ndatos=Nodo::crear(),"visitados");
				}
				return $ndatos;
/*
				$ndatos=null;
				if (!$ndatos=$this->datos_temporales("buscar")){
					//echo "no tenia datos temporales";
					if(!$crear){
						return null;
					}
					$this->_datos_temporales("buscar=");
					$ndatos=$this->datos_temporales("buscar");
				}
				return $ndatos;*/
			}
			/*-------------------------------------------
				Datos de salida: 
					True si tuvo exito, false en caso contrario
			+--------------------------------------------
				Poscondiciones:
			+--------------------------------------------
		<-----------------------Fin de visitados_auxiliar_crear_obtener_lista()*/

		/* FUNCION recordar_posicion() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso: Guardar posicion actual en la lista de visitados
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
					guarda la posicion actual sin importar como este la bandera de guardar_visitados
			+--------------------------------------------
				Cuerpo:
			*/
			public function recordar_posicion(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::recordar_posicion() el iterador no esta ocupado");
					return false;
				}	
				if (!$actual=$cuerpo->adyacente("actual")){
					Iterador::_error("Iterador::recordar_posicion() el iterador no tiene actual");
					return false;
				}	
								
				$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
				
				$this->guardar_visitado_interno($ndatos, $actual);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
					true;
			+--------------------------------------------
				Poscondiciones:
					quedo guardada la posicion actual en la lista de visitados
			+--------------------------------------------
		<-----------------------Fin de visitados_auxiliar_crear_obtener_lista()*/
		/* FUNCION limpiar_lista_de_visitados() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso:  Limpiar la lista nodos de visitados guardados
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function limpiar_lista_de_visitados(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::recordar_posicion() el iterador no esta ocupado");
					return false;
				}	
								
				$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
				
				$this->destruir_visitados_guardados($ndatos);
				return true;
			}
			/*-------------------------------------------
				Datos de salida: 
					true;
			+--------------------------------------------
				Poscondiciones:
					quedo guardada la posicion actual en la lista de visitados
			+--------------------------------------------
		<-----------------------Fin de limpiar_lista_de_visitados()*/
				/* FUNCION guardar_recorrido() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso:  Devolver estado de guardar recorrido
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function guardar_recorrido(){
				if (!$cuerpo=$this->raiz_cuerpo){
					Iterador::_error("Iterador::guardar_recorrido() el iterador no esta ocupado");
					return false;
				}	
				return (bool)$cuerpo->adyacente("guaradar recorrido");
			}
			/*-------------------------------------------
				Datos de salida: 
					true;
			+--------------------------------------------
				Poscondiciones:
					quedo guardada la posicion actual en la lista de visitados
			+--------------------------------------------
		<-----------------------Fin de limpiar_lista_de_visitados()*/


				
		/* FUNCION inicio() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso:  posiciona al iterador en la primer posicion recordada
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function inicio(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					self::_error("Iterador::inicio() el iterador no esta ocupado");
					return false;
				}	
								
				$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
				if (!$nlista=$ndatos->adyacente("lista de recorridos")){
					//$ndatos->imprimir();
					self::_error("Iterador::inicio() error en la estructura");
					return null;
				}			
				$nprimero=$nlista->adyacente("primero");
				if ($nprimero){
					$cuerpo->_adyacente_en($aux=$nprimero->adyacente("referencia"),"actual");
					return $aux;
				}else{
					return null;
				}
			}
			/*-------------------------------------------
				Datos de salida: 
					la primera posicion recordada en caso de exito, null en caso contrario
			+--------------------------------------------
				Poscondiciones:
					queda la posicion actual en la primera posicion recordada
			+--------------------------------------------
		<-----------------------Fin de inicio()*/
		/* FUNCION ultimo() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso:  posiciona al iterador en la ultima posicion recordada
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
			public function ultimo(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					self::_error("Iterador::ultimo() el iterador no esta ocupado");
					return false;
				}	
								
				$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
				if (!$nlista=$ndatos->adyacente("lista de recorridos")){
					//$ndatos->imprimir();
					self::_error("Iterador::ultimo() error en la estructura");
					return null;
				}			
				$nultimo=$nlista->adyacente("ultimo");
				if ($nultimo){
					$cuerpo->_adyacente_en($aux=$nultimo->adyacente("referencia"),"actual");
					return $aux;
				}else{
					return null;
				}
			}
			/*-------------------------------------------
				Datos de salida: 
					la ultima posicion recordada en el caso de exito o null en caso contrario
			+--------------------------------------------
				Poscondiciones:
					queda la posicion actual en la ultima posicion recordada
			+--------------------------------------------
		<-----------------------Fin de ultimo()*/

		/* FUNCION ultimo() ------------------------------------------->
				Interfaz: Visitados  
			+--------------------------------------------
				Caso de uso:  posiciona al iterador en la ultima posicion recordada
			+--------------------------------------------
				Precondiciones: 
			+--------------------------------------------
				Datos de entrada:
			+--------------------------------------------
				Notas:
			+--------------------------------------------
				Cuerpo:
			*/
		/*	public function ultimo(){
				if ((!$cuerpo=$this->raiz_cuerpo)or(!$cuerpo->adyacente("ocupado"))){
					Iterador::_error("Iterador::ultimo() el iterador no esta ocupado");
					return false;
				}	
								
				$ndatos=$this->visitados_auxiliar_crear_obtener_lista($cuerpo);
				if (!$nlista=$ndatos->adyacente("lista de recorridos")){
					$ndatos->imprimir();
					terador::_error("Iterador::ultimo() error en la estructura");
					return null;
				}			
				$nultimo=$nlista->adyacente("ultimo");
				if ($nultimo){
					$cuerpo->_adyacente_en($aux=$nultimo->adyacente("referencia"),"actual");
					return $aux;
				}else{
					return null;
				}
			}
			/*-------------------------------------------
				Datos de salida: 
					la ultima posicion recordada en el caso de exito o null en caso contrario
			+--------------------------------------------
				Poscondiciones:
					queda la posicion actual en la ultima posicion recordada
			+--------------------------------------------
		<-----------------------Fin de ultimo()*/

/*	static public function imprimir_alertas(){
		echo "LLALALLA";
		parent::imprimir_alertas();
	}*/
/*	static public function imprimir_alertas(){
		echo "LLALALLA";
		parent::imprimir_alertas();
	}*/
}

include_once("IteradorArbol.php");
 
$it=Iterador::iterador("sapo");
$it->activar_guardar_visitados();
$it->_actual($nac=Nodo::nodo("a"));
$it->activar_guardar_visitados();
$it->desactivar_guardar_visitados();

$it->_adyacente_en($nb=Nodo::nodo("b"), "b");
$it->_adyacente_en(Nodo::nodo("c"), "c", "b");
$ne=$it->_adyacente_en(Nodo::nodo("e"), "e", "b;c");
$it->_adyacente_en(Nodo::nodo("d"), "d", "b");
$it->_avanzar("e", $ne, "b;d");
$it->_actual($nac);

$it->avanzar("hhhh","ololo");
$it->inicio();
$it->ultimo();



$it->desocupar();
$it=Iterador::iterador("perro", "sopapo");
$it->destruir();

$it->desocupar();
$it2=Iterador::iterador("perro");
echo $it2->nombre();
//$it2->_error("erro gato");
/*echo "ECHO<br/>";
$semaforosex=explode(";",'lkjlkjlk/;');
$cont=0;
foreach ($semaforosex as $i => $semaforo){
	$cont++;
	echo "PPP".$cont."PP".$i;
}*/
//$it=Iterador::iterador("it");
//$it->camino("/*hol////a/M>/2/;/3;/>papa>/>123/;");
Nodo::imprimir_superestructura();
Objeto::imprimir_errores();
Objeto::imprimir_alertas();
?>