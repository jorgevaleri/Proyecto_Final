<?php
session_name();

//AGREGUE ESE IF, SINO VA session_start(); SOLO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//

$dbhost = "localhost";
$dbusuario = "root";
$dbpassword = "";
$db = "seminario";

//CONECTAR A LA BASE DE DATOS//
$conexion = mysqli_connect($dbhost, $dbusuario, $dbpassword, $db);

if (!$conexion) {
	//SI NO SE PUEDO CONECTAR, MUESTRA UN MENSAJE CON JAVASCRIPT//
	echo "<script >	
				alert('no se pudo conectar al sistema');
				location.href ='index.php';
	   		</script>";
	exit();
} else {
	//LA CONEXION FUE EXITOSA//
	/*echo "<script >
			alert('Se conecto correctamente al sistema');
		</script>";*/
}
