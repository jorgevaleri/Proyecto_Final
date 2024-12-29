<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="css/header.css">
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

<body>

    <?php
    // Obtener el nombre del archivo de la página actual
    $currentPage = basename($_SERVER['SCRIPT_FILENAME']);
    ?>

    <header>
        <div class="ancho">
            <div class="logo">
                <a href="index.php"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
            </div>

            <nav>
                <ul>
                    <?php if ($currentPage == 'index.php'): ?>
                        <!-- Si estamos en la página de inicio, mostramos Iniciar Sesión y Registrarse -->
                        <li><a href="logeo.php">Iniciar Sesión</a></li>
                        <li><a href="registrarse.php">Registrarse</a></li>

                    <?php elseif ($currentPage == 'registrarse.php'): ?>
                        <!-- Si estamos en la página de registro, mostramos Inicio e Iniciar Sesión -->
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="logeo.php">Iniciar Sesión</a></li>

                    <?php elseif ($currentPage == 'logeo.php'): ?>
                        <!-- Si estamos en la página de iniciar sesión, mostramos Inicio y Registrarse -->
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="registrarse.php">Registrarse</a></li>

                    <?php else: ?>
                        <!-- Otros casos: puedes añadir más páginas si lo necesitas -->
                        <li><a href="index.php">Inicio</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

</body>

</html>