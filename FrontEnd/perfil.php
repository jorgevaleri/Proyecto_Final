<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>	

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/docente.css">
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
</head>

<body class="body">
    <header>
        <div class="ancho">
            <div class="logo">
                <a href="#"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
            </div>

            <nav>
                <ul>
                    <li><a href="menu_principal.php">Inicio</a></li>
                    <li><a href="perfil.php"><?php echo $_SESSION['personas_nombre']; ?></a></li>
                    <li><a href="deslogeo.php">Cerrar Sesion</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- FALTA -->

    <aside class="cuerpo-menu-vertical">
        <div>
            <ul class="menu-vertical">
                <li><a href="#">Escuelas</a>
                    <?php
                    $c=0;
                    $sql1="SELECT escuelas_id, escuelas_nombre FROM escuelas";
                    $resul1=mysqli_query($conexion,$sql1);
                    ?>

                    <ul class="submenu-vertical">

                        <?php
                        $cant=mysqli_num_rows($resul1);
                        for($i=0; $i<$cant; $i++)
                        {
                            $reg=mysqli_fetch_row($resul1);
                            ?>

                            <li class="sin-seleccion"><a href="escuelas.php?tipo=1&id=<?php echo $reg[0]; ?>"><?php echo $reg[1];?></a></li>
                            <?php
                        }
                        ?>
                <li class="seleccion"><a href="escuelas_agregar.php">Agregar Escuela</a></li>
            </ul>
            </li>
                <br>
                <li><a href="#">Director</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-docente.php">Mayra Segui</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Docentes</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-docente.php">Jorge Valeri</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 2</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 3</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Formaciones Profesionales</a>
                    <?php
                    $c=0;
                    $sql1="SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales";
                    $resul1=mysqli_query($conexion,$sql1);
                    ?>

                    <ul class="submenu-vertical">

                        <?php
                        $cant=mysqli_num_rows($resul1);
                        for($i=0; $i<$cant; $i++)
                        {
                            $reg=mysqli_fetch_row($resul1);
                            ?>

                            <li class="sin-seleccion"><a href="resumen.php?tipo=1&id=<?php echo $reg[0]; ?>"><?php echo $reg[1];?></a></li>
                            <?php
                        }
                        ?>
                </li>
                <li class="seleccion"><a href="director-agregar-fp.php">Agregar F.P.</a></li>
                <br>
                <li><a href="#">Permisos</a></li>
                <br>
                <li><a href="#">Auditorias</a></li>
            </ul>
        </div>
    </aside>
    
    <!-- FALTA -->

    <main class="cuerpo">
    <?php 
	if(isset($_GET['id'])){
		
		$id=$_GET['id'];
		$tipo=$_GET['tipo'];
		
		$sql="SELECT usuarios_id, personas_apellido, personas_nombre, personas_dni, DATE_FORMAT(personas_fechnac, '%d-%m-%Y'), personas_edad, personas_sexo, telefonos_numero, domicilios_calle, domicilios_altura, localidades_nombre, provincias_nombre, paises_nombre, usuarios_email, usuarios_email, DATE_FORMAT(personas_fechcrea, '%d-%m-%Y') FROM usuarios INNER JOIN personas ON usuarios.personas_id=personas.personas_id INNER JOIN telefonos ON telefonos.personas_id=personas.personas_id INNER JOIN domicilios ON domicilios.personas_id=personas.personas_id INNER JOIN localidades ON localidades.localidades_id=domicilios.localidades_id INNER JOIN paises ON localidades.paises_id=paises.paises_id WHERE usuarios_id='$id'";

		$resul=mysqli_query($conexion,$sql);
		
		$cant=mysqli_num_rows($resul);
		
		if($cant>0)
		{
			$row=mysqli_fetch_row($resul);
			$_POST['personas_apellido']=$row[1];
			$_POST['personas_nombre']=$row[2];
			$_POST['personas_dni']=$row[3];
			$_POST['personas_fechnac']=$row[4];
			$_POST['sexos_nombre']=$row[5];
			$_POST['correos_denominacion']=$row[6];
			$_POST['roles_nombre']=$row[7];
			$_POST['estados_nombre']=$row[8];
			$_POST['personas_fechalta']=$row[9];
			
					
			?>
        <div class="container">
            <div class="title"><?php echo $_SESSION['personas_nombre']; ?></div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">DNI / CUIL</span>
                            <input type="text" value="34915485">
                        </div>
                        <div class="input-box">
                            <span class="details">Apellidos</span>
                            <input type="text" value="Segui">
                        </div>
                        <div class="input-box">
                            <span class="details">Nombres</span>
                            <input type="text" value="Mayra Fabricia del Valle">
                        </div>
                        <div class="input-box">
                            <span class="details">Fecha de Nacimiento</span>
                            <input type="date" value="1990-05-01">
                        </div>
                        <div class="input-box">
                            <span class="details">Edad</span>
                            <input type="text" value="33">
                        </div>
                        <div class="input-box">
                            <span class="details">Sexo</span>
                            <input type="text" value="Femenino">
                        </div>
                        <div class="input-box">
                            <span class="details">Telefono</span>
                            <input type="text" value="3834797979">
                        </div>
                        <div class="input-box">
                            <span class="details">Direccion</span>
                            <input type="text" value="Ocampo 1589">
                        </div>
                        <div class="input-box">
                            <span class="details">Localidad</span>
                            <input type="text" value="San Fernando del Valle de Catamarca">
                        </div>
                        <div class="input-box">
                            <span class="details">Lugar de Nacimiento</span>
                            <input type="text" value="Catamarca">
                        </div>
                        <div class="input-box">
                            <span class="details">Nacionalidad</span>
                            <input type="text" value="Argentino">
                        </div> 
                        <div class="input-box">
                            <span class="details">Formacion Profesional</span>
                            <input type="text" value="Informatica">
                        </div>
                        <div class="input-box">
                            <span class="details">Escuelas</span>
                            <input type="text" value="EDJA 38">
                        </div>
                        <div class="input-box">
                            <span class="details">Tipo</span>
                            <input type="text" value="Director">
                        </div>
                        <div class="input-box">
                            <span class="details">Correo Electronico</span>
                            <input type="email" value="may@segui.com">
                        </div>                       
                    </div>
                </form>
            </div>
        </div>   
            
        <ul class="botones">
            <li class="boton-aceptar"><a href="director-modificar.php"> Modificar</a></li>
            <li class="boton-aceptar"><a href="director-cambiar-contraseña.php"> Cambiar contraseña</a></li>
        </ul>
        <?php
    }else{
			?>
			<script >	
				alert('No existe el usuario enviado');
				location.href ='usuarios.php';
	   		</script>	
			<?php
		}
			
	}else{
			?>
			<script >	
				alert('Ud ingreso mal');
				location.href ='index.php';
	   		</script>	
			<?php
	}

	?>
    </main>
</body>

<footer class="pie">
    <div class="pie_1">
    </div>

    <section class="pie_iconos">
        <a href="https://www.facebook.com/jotta.valeri/" class="bi bi-facebook"></a>
        <a href="https://www.instagram.com/jotta_vs/" class="bi bi-instagram"></a>
        <a href="https://twitter.com/" class="bi bi-twitter"></a>
        <a href="https://wa.me/+543834800300" class="bi bi-whatsapp"></a>
        <a href="https://goo.gl/maps/ZdaDwSRw5DedrJXj6" class="bi bi-geo-alt-fill"></a>
    </section>

    <div class="copyright">
    </div>
</footer>

</html>