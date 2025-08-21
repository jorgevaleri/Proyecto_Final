<?php
session_name();

//AGREGUE ESE IF, SINO VA session_start(); SOLO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//CONFIGURACION DE CONEXION A LA BASE DE DATOS
$dbhost = "localhost";
$dbusuario = "root";
$dbpassword = "";
$db = "seminario";

//CONECTAR A LA BASE DE DATOS
$conexion = mysqli_connect($dbhost, $dbusuario, $dbpassword, $db);

if (!$conexion) {
	//SI NO SE PUEDO CONECTAR, MUESTRA UN MENSAJE CON JAVASCRIPT//
	echo "<script >	
				alert('no se pudo conectar al sistema');
				location.href ='index.php';
	   		</script>";
	exit();
}

//CONFIGURACION DE HEADERS PARA EVITAR EL ALMACENAMIENTO EN CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // Deshabilita el caché
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilidad
header("Pragma: no-cache"); // Deshabilita el caché HTTP 1.0
?>