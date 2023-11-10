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
    <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.js"></script>
    <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.css" rel="stylesheet" />

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
                <li class="sin-seleccion"><a href="escuela.php">Escuelas</a></li>
                <br>
                <li class="sin-seleccion"><a href="persona.php">Personas</a></li>
                <br>
                <li class="sin-seleccion"><a href="formacion_profesional.php">Form. Prof.</a></li>
                <br>
                <li class="sin-seleccion"><a href="#">Permisos</a></li>
                <br>
                <li class="sin-seleccion"><a href="#">Auditorias</a></li>
            </ul>
        </div>
    </aside>
    
    <!-- FALTA -->

    <main class="cuerpo">
        <div>

            <div class="title">Agregar Nueva Escuela</div>

            <br>

            <?php

            $c = 0;

            if (isset($_POST['enviar'])) {
                $escuelas_id = $_POST['escuelas_id'];
                $escuelas_nombre = $_POST['escuelas_nombre'];
                $escuelas_cue = $_POST['escuelas_cue'];

                if (trim($escuelas_nombre) == '') {
                    $c = 1;
                    $error1 = "Debe ingresar una Escuela";
                }

                if (trim($escuelas_cue) == '') {
                    $c = 1;
                    $error2 = "Debe ingresar un CUE";
                } else {
                    if (!is_numeric($escuelas_cue)) {
                        $c = 1;
                        $error2 = "Ingrese un valor numerico";
                    }
                }

                if ($c == 0) {
                    $sql = "INSERT INTO escuelas(escuelas_nombre, escuelas_cue) VALUES ('$escuelas_nombre', '$escuelas_cue')";

                    $result = mysqli_query($conexion, $sql);
                    if (mysqli_errno($conexion) == 0) {
                        $id = mysqli_insert_id($conexion);
            ?>

                        <script>
                            //    alert('Se cargo correctamente los datos ');
                            location.href = 'escuela.php?id=<?php echo $id; ?>';
                        </script>

                    <?php

                    } else {
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
                                <p>NOMBRE:
                                    <input name="escuelas_nombre" type="text" id="escuelas_nombre" value="<?php echo $_POST['escuelas_nombre']; ?>" size="30" maxlength="60">
                                    <?php

                                    if (isset($error1)) {
                                        echo $error1;
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="50">
                                <p>CUE:
                                    <label for="escuelas_cue"></label>
                                    <input name="escuelas_cue" type="text" id="escuelas_cue" size="15" maxlength="20" value="<?php echo $_POST['escuelas_cue']; ?>">
                                    <?php

                                    if (isset($error2)) {
                                        echo $error2;
                                    }

                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="42" align="center">
                                <p class="boton-agregar">
                                    <input type="submit" name="enviar" id="enviar" value="Agregar">
                                </p>

                                <p>
                                    <a href="escuela.php">volver </a>
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