<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">

    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/fp.css">

</head>

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

<body class="body">
    <!-- FALTA -->

    <aside class="cuerpo-menu-vertical">
        <div>
            <ul class="menu-vertical">
                <li><a href="escuelas.php">Escuelas</a></li>
                <br>
                <li><a href="#">Personas</a></li>
                <br>
                <li><a href="formacion_profesional.php">Form. Prof.</a></li>
                <br>
                <li><a href="#">Permisos</a></li>
                <br>
                <li><a href="#">Auditorias</a></li>
            </ul>
        </div>
    </aside>

    <!-- FALTA -->

    <main class="cuerpo">
        <h1 class="title">Agregar Nueva Formacion Profesional</h1>
        <!-- <div class="title">Agregar Nueva Formacion Profesional</div> -->
        <br>

        <?php

        $c = 0;

        if (isset($_POST['enviar'])) {
            $formaciones_profesionales_id = $_POST['formaciones_profesionales_id'];
            $formaciones_profesionales_nombre = $_POST['formaciones_profesionales_nombre'];
            $formaciones_profesionales_eliminado = $_POST['formaciones_profesionales_eliminado'];

            if (trim($formaciones_profesionales_nombre) == '') {
                $c = 1;
                $error1 = "Debe ingresar una Formacion Profesional";
            }

            if($c==0)
		{
			$sql="INSERT INTO formaciones_profesionales(formaciones_profesionales_nombre) VALUES ('$formaciones_profesionales_nombre')";

			$result=mysqli_query($conexion,$sql);
			if (mysqli_errno($conexion)==0)
			{
				$id=mysqli_insert_id($conexion);
				?>
       	     	
				<script>
                       alert('Se cargo correctamente los datos ');
					   location.href ='formacion_profesional.php?id=<?php echo $id; ?>';
                </script>

				<?php		
				
			}
			else
			{
				echo "No se cargo correctamente<br>";
			}
					
			?>		
			
			<?php
		}

        }

        if (!isset($_POST['enviar']) or $c != 0) {
        ?>
            <table>
                <form id="form1" name="form1" method="post">

                    <tr>
                        <td height="42">
                            <p>Nombre:
                                <input name="formaciones_profesionales_nombre" type="text" id="formaciones_profesionales_nombre" value="<?php echo $_POST['formaciones_profesionales_nombre']; ?>" size="30" maxlength="60">
                                <?php

                                if (isset($error1)) {
                                    echo $error1;
                                }
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td height="42" align="center">
                            <p>
                                <input type="submit" name="enviar" id="enviar" value="Agregar">
                            </p>

                            <p>
                                <a href="formacion_profesional.php">volver </a>
                            </p>
                        </td>
                    </tr>
                </form>
            </table>

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