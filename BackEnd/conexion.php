<?php
session_name();
session_start();

$dbhost="localhost";
$dbusuario="root";
$dbpassword="";
$db="seminario";
$conexion = mysqli_connect($dbhost,$dbusuario,$dbpassword,$db);	
	
if(!$conexion){
			?>
			<script >	
				alert('no se pudo conectar al sistema');
				location.href ='index.php';
	   		</script>
	   		<?php
}else{
			?>
			<script >	
				//alert('Se conecto correctamente al sistema');
	   		</script>
	   		<?php
}
?>


