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
                <li><a href="escuela.php">Escuelas</a></li>
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
        <div>

            <div class="title">Formaciones Profesionales</div>

            <br>

            <ul class="botones">
                <li class="boton-volver"><a href="menu_principal.php"><i class="bi bi-arrow-left-circle"></i> Volver</a></li>
                <li class="boton-agregar"><a href="formacion_profesional_agregar.php"><i class="bi bi-plus-circle"></i> Agregar</a></li>
            </ul>


            <br>

            <?php
            $c = 0;

            $sql1 = "SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_eliminado='0'";

            $resul1 = mysqli_query($conexion, $sql1);

            ?>
            <div class="contenedor">
                <table class="table table-striped table-hover" width="100">
                    <tr>
                        <td width="10%"></td>
                        <td width="60%">FORMACION PROFESIONAL</td>
                        <td width="30%">ACCIONES</td>
                    </tr>

                    <tbody class="table-group-divider">
                        <?php

                        $cant = mysqli_num_rows($resul1);
                        $cont = 0;

                        for ($i = 0; $i < $cant; $i++) {
                            $reg = mysqli_fetch_row($resul1);
                            // contador
                            $cont++;

                        ?>

                            <tr>
                                <td><?php echo $cont; ?></td>
                                <td><?php echo $reg[1]; ?></td>
                                <td>
                                    <a href="formacion_profesional_editar.php?tipo=1&id=<?php echo $reg[0]; ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="formacion_profesional_eliminar.php?tipo=2&id=<?php echo $reg[0]; ?>" title="Eliminar"><i class="bi bi-trash3" style="color: red;"></i></a>
                                </td>
                            </tr>

                        <?php
                        }
                        ?>
                    </tbody>
            </div>
            </table>


        </div>

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