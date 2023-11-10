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

            <div class="title">Agregar Nueva Persona</div>

            <br>

            <?php

            $c = 0;

            if (isset($_POST['enviar'])) {
                $personas_id = $_POST['personas_id'];
                $personas_dni = $_POST['personas_dni'];
                $personas_apellido = $_POST['personas_apellido'];
                $personas_nombre = $_POST['personas_nombre'];
                $personas_fechnac = $_POST['personas_fechnac'];
                $personas_edad = $_POST['personas_edad'];
                $personas_sexo = $_POST['personas_sexo'];

                if (trim($personas_dni) == '') {
                    $c = 1;
                    $error1 = "Debe ingresar un DNI";
                } else {
                    if (!is_numeric($personas_dni)) {
                        $c = 1;
                        $error1 = "Ingrese un valor numerico";
                    }
                }

                if (trim($personas_apellido) == '') {
                    $c = 1;
                    $error2 = "Debe ingresar un Apellido";
                }

                if (trim($personas_nombre) == '') {
                    $c = 1;
                    $error3 = "Debe ingresar un Nombre";
                }

                if (trim($personas_fechnac) == '') {
                    $c = 1;
                    $error4 = "Debe ingresar una fecha";
                }

                if (trim($personas_edad) == '') {
                    $c = 1;
                    $error5 = "Debe ingresar una Edad";
                } else {
                    if (!is_numeric($personas_edad)) {
                        $c = 1;
                        $error5 = "Ingrese un valor numerico";
                    }
                }

                if (trim($personas_sexo) == '') {
                    $c = 1;
                    $error6 = "Debe seleccionar un sexo";
                }

                if ($c == 0) {
                    $sql = "INSERT INTO personas(personas_dni, personas_apellido, personas_nombre, personas_fechnac, personas_edad, personas_sexo) VALUES ('$personas_dni', '$personas_apellido', '$personas_nombre', '$personas_fechnac', '$personas_edad', '$personas_sexo')";

                    $result = mysqli_query($conexion, $sql);
                    if (mysqli_errno($conexion) == 0) {
                        $id = mysqli_insert_id($conexion);
            ?>

                        <script>
                            //    alert('Se cargo correctamente los datos ');
                            location.href = 'persona.php?id=<?php echo $id; ?>';
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
                                <p>DNI:
                                    <input name="personas_dni" type="text" id="personas_dni" value="<?php echo $_POST['personas_dni']; ?>" size="30" maxlength="60">
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
                                <p>APELLIDO:
                                    <label for="personas_apellido"></label>
                                    <input name="personas_apellido" type="text" id="personas_apellido" size="15" maxlength="20" value="<?php echo $_POST['personas_apellido']; ?>">
                                    <?php

                                    if (isset($error2)) {
                                        echo $error2;
                                    }

                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="50">
                                <p>NOMBRE:
                                    <label for="personas_nombre"></label>
                                    <input name="personas_nombre" type="text" id="personas_nombre" size="15" maxlength="20" value="<?php echo $_POST['personas_nombre']; ?>">
                                    <?php

                                    if (isset($error3)) {
                                        echo $error3;
                                    }

                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="47">
                                <p>FECHA DE NACIMIENTO:
                                    <input type="date" name="personas_fechnac" id="personas_fechnac" value="<?php echo $_POST['personas_fechnac']; ?>">
                                    <?php

                                    if (isset($error4)) {
                                        echo $error4;
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="42">
                                <p>EDAD:
                                    <input name="personas_edad" type="text" id="personas_edad" value="<?php echo $_POST['personas_edad']; ?>" size="30" maxlength="60">
                                    <?php

                                    if (isset($error5)) {
                                        echo $error5;
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <td height="47">
                                <p>SEXO: </p>
                                <select name="personas_sexo">
                                    <option value="no" selected>Seleccione:</option>
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                </select>

                                <?php

                                if (isset($error6)) {
                                echo $error6;
                                }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <td height="42" align="center">
                                <p class="boton-agregar">
                                    <input type="submit" name="enviar" id="enviar" value="Agregar">
                                </p>

                                <p>
                                    <a href="persona.php">volver </a>
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