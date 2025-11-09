<?php
// SESIÓN: NOMBRE E INICIALIZACION
session_name();

//SI LA SESIÓN NO ESTA INICIADA, LA INICIAMOS
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
	//SI NO SE PUEDO CONECTAR, MUESTRA UN MENSAJE CON JAVASCRIPT
	echo "<script >	
				alert('no se pudo conectar al sistema');
				location.href ='index.php';
	   		</script>";
	exit();
}

//HEADER PARA EVITAR ALMACENAMIENTO EN CACHE POR PARTE DEL NAVEGADOR
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); // DESHABILITA EL CACHE
header("Cache-Control: post-check=0, pre-check=0", false); // COMPATIBILIDAD
header("Pragma: no-cache"); // DESHABILITA EL CACHE HTTP 1.0
?>