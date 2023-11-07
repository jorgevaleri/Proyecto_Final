<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/editar-alumno.css">
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
                <a href="index.html"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
            </div>

            <nav>
                <ul>
                    <li><a href="docente.html">Docente</a></li>
                    <li><a href="index.html">Cerrar Sesion</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <aside class="cuerpo-menu-vertical">
        <div class="contenedor-menu-vertical">
            <ul class="menu-vertical">
                <li><a href="#">Escuelas</a>
                    <ul class="submenu-vertical">
                        <li class="seleccion"><a href="docente-registro.html">EDJA N° 38</a></li>
                        <li class="sin-seleccion"><a href="#">Escuela 2</a></li>
                        <li class="sin-seleccion"><a href="#">Escuela 3</a></li>
                        <li><a href="agregar-escuela.html">Agregar Escuela</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </aside>



    <main class="cuerpo">
        <div class="container">
            <div class="title">Modificar alumno</div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">DNI</span>
                            <input type="text" value="36844411" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Apellidos</span>
                            <input type="text" value="Aranda" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nombres</span>
                            <input type="text" value="Georgina Elizabeth" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Fecha de Nacimiento</span>
                            <input type="date" value="1992-01-18" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Edad</span>
                            <input type="text" value="31" required>
                        </div>
                        <div class="select-box">
                            <span class="details">Sexo</span>
                            <select name="sexo">
                                <option value="1">Masculino</option>
                                <option value="2" selected>Femenino</option>
                            </select required>
                        </div>
                        <div class="input-box">
                            <span class="details">Telefono</span>
                            <input type="text"  value="3834651363" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Direccion</span>
                            <input type="text" value="Casa S/N - Las Tejas - Valle Viejo" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Localidad</span>
                            <input type="text" value="Valle Viejo" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Lugar de Nacimiento</span>
                            <input type="text" value="Catamarca" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nacionalidad</span>
                            <input type="text" value="Argentino" required>
                        </div>                        
                    </div>
                </form>
            </div>
        </div>   
            
        <ul class="botones">
            <li class="boton-cancelar"><a href="docente-alumnos.html"> Cancelar</a></li>
            <li class="boton-aceptar"><a href="docente-alumnos.html"> Guardar Cambios</a></li>
        </ul>
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