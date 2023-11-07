<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/escuelas.css">
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.js"></script>
    <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.css" rel="stylesheet" />
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

    <aside class="cuerpo-menu-vertical">
        <div class="contenedor-menu-vertical">
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

                            <li class="seleccion"><a href="escuelas.php"><?php echo $reg[1];?></a></li>
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
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-resumen.php">Informatica</a></li>
                        <li class="sin-seleccion"><a href="#">Herreria</a></li>
                        <li class="sin-seleccion"><a href="#">Peluqueria</a></li>
                        <li class="seleccion"><a href="director-agregar-fp.php">Agregar F.P.</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Permisos</a></li>
            </ul>
        </div>
    </aside>

    <main class="cuerpo">

    <?php 
    if(isset($_GET['id']))
    {
		$id=$_GET['id'];
		$tipo=$_GET['tipo'];
		
		$sql="SELECT escuelas_id, escuelas_nombre, escuelas_cue, domicilios_calle, domicilios_altura, domicilios_latitud, domicilios_longitud FROM escuelas INNER JOIN domicilios ON escuelas.domicilios_id=domicilios.domicilios_id WHERE escuelas_id='$id'";

		$resul=mysqli_query($conexion,$sql);
		
		$cant=mysqli_num_rows($resul);

        if($cant>0)
		{
			$row=mysqli_fetch_row($resul);
			$_POST['escuelas_id']=$row[0];
			$_POST['escuelas_nombre']=$row[1];
			$_POST['escuelas_cue']=$row[2];
			$_POST['domicilios_calle']=$row[3];
			$_POST['domicilios_altura']=$row[4];
            $_POST['domicilios_latitud']=$row[5];
            $_POST['domicilios_longitud']=$row[6];
    
        ?>
        <div class="container">
            <div class="title">Escuela</div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">Escuela</span>
                            <input name="escuelas_nombre" type="text" id="escuelas_nombre" value="<?php echo $_POST['escuelas_nombre']; ?>" size="30" maxlength="60">
                        </div>

                        <div class="input-box">
                            <span class="details">CUE</span>
                            <input name="escuelas_cue" type="text" id="escuelas_cue" value="<?php echo $_POST['escuelas_cue']; ?>" size="30" maxlength="60">
                        </div>

                        <div class="input-box">
                            <span class="details">Direccion</span>
                            <input name="domicilios_calle" type="text" id="domicilios_calle" value="<?php echo $_POST['domicilios_calle']; ?>" size="30" maxlength="60">
                            <input name="domicilios_altura" type="text" id="domicilios_altura" value="<?php echo $_POST['domicilios_altura']; ?>" size="30" maxlength="60">
                        </div>

                        <div>
                            <!-- FALTA -->

                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d876.7495245562889!2d-65.78210614747168!3d-28.479603920053496!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x942428c2cd482d1b%3A0x1b3c64ec1146b63f!2sJun%C3%ADn%20%26%20Florida%2C%20San%20Fernando%20del%20Valle%20de%20Catamarca%2C%20Catamarca!5e0!3m2!1ses!2sar!4v1691067308837!5m2!1ses!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            
                            <!-- FALTA -->
                        </div>                 
                    </div>
                </form>
            </div>
        </div>   
            
        <ul class="botones">
            <li class="boton-aceptar"><a href="menu_principal.php"> Volver</a></li>
            <li class="boton-aceptar"><a href="director-modificar-escuela.php"> Modificar</a></li>
        </ul>

        <?php
			
	    }
        else
        {
            ?>
            <script >	
                alert('No existe la escuela enviada');
                location.href ='menu_principal.php';
            </script>	
            <?php
	    }			
	}
    else
    {
        ?>
        <script >	
            alert('Ud ingreso mal');
            location.href ='menu_principal.php';
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