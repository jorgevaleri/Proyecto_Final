<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../BackEnd/conexion.php"); ?>	

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/inicio_sesion.css">
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<header>
    <div class="ancho">
        <div class="logo">
            <a href="index.php"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
        </div>

        <nav>
            <ul>
                <li><a href="index.php" class="bi bi-house-door-fill">  Inicio</a></li>
                <li><a href="registrarse.php">Registrarse</a></li>
            </ul>
        </nav>
    </div>
</header>

<body class="body">
<?php

$c=0;
if(isset($_POST['ingresar'])) 
{
	$usuarios_email=$_POST['usuarios_email'];
	$usuarios_clave=$_POST['usuarios_clave'];
	
	$sql="SELECT usuarios_email, usuarios_clave, personas_nombre FROM usuarios INNER JOIN personas ON usuarios.personas_id=personas.personas_id WHERE usuarios_email='$usuarios_email' AND usuarios_clave='$usuarios_clave'";
	
	$result=mysqli_query($conexion,$sql);

	if(mysqli_num_rows($result)>0)
	{
		$reg=mysqli_fetch_row($result);
		$_SESSION['usuarios_email']=$reg[0];
        $_SESSION['personas_nombre']=$reg[2];

        ?>             
		<script>
		location.href ='menu_principal.php';
		</script>
		<?php
	}
	else
	{

    ?>
    <script>alert('el e-mail <?php echo $usuarios_email; ?> no existe');</script>
    <?php
    $c=1;
	}
}
	
if(!isset($_POST['ingresar']) or $c!=0){	
	?>
    <main class="cuerpo">
        
        <div class="content">
            <form id="form1" name="form1" method="post">
                <h3 class="title">Iniciar Sesion</h3>

                <div class="field">
                    <input name="usuarios_email" type="text" id="usuarios_email" required>
                    <span class="fas fa-user"></span>
                    <label for="usuarios_email">Correo Electronico</label>
                </div>

                <div class="field">
                    <input type="password" name="usuarios_clave" id="usuarios_clave" required>
                    <span class="fas fa-lock"></span>
                    <label for="usuarios_clave">Contraseña</label>
                </div>

                <br>

                <div class="boton">
                    <input type="submit" name="ingresar" id="ingresar" value="Ingresar">
                </div>
                
                <!-- FALTA -->
                <div class="sign-up">
                    <input type="checkbox" value="Recuerdame">
                    Recuerdame
                </div>
                <div class="forgot-pass">
                    <a href="olvide-contraseña.php">Olvide la contraseña</a>
                </div>
                <!-- FALTA -->
            </form>
        </div>
    </main>
    <?php
	}
	?>
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