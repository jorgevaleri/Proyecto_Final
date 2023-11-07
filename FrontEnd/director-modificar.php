<!DOCTYPE html>
<html lang="en">

<head>
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
                <a href="index.html"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
            </div>

            <nav>
                <ul>
                    <li><a href="director.html">Director</a></li>
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
                        <li class="sin-seleccion"><a href="director-escuela.html">EDJA N° 38</a></li>
                        <li class="seleccion"><a href="director-agregar-escuela.html">Agregar Escuela</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Docentes</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-docente.html">Jorge Valeri</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 2</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 3</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Formaciones Profesionales</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-resumen.html">Informatica</a></li>
                        <li class="sin-seleccion"><a href="#">Herreria</a></li>
                        <li class="sin-seleccion"><a href="#">Peluqueria</a></li>
                        <li class="seleccion"><a href="director-agregar-fp.html">Agregar F.P.</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </aside>



    <main class="cuerpo">
        <div class="container">
            <div class="title">Modificar Director</div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">DNI / CUIL</span>
                            <input type="text" value="34915485" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Apellidos</span>
                            <input type="text" value="Segui" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nombres</span>
                            <input type="text" value="Mayra Fabricia del Valle" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Fecha de Nacimiento</span>
                            <input type="date" value="1990-05-01" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Edad</span>
                            <input type="text" value="33" required>
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
                            <input type="text" value="3834797979" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Direccion</span>
                            <input type="text" value="Ocampo 1589" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Localidad</span>
                            <input type="text" value="San Fernando del Valle de Catamarca" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Lugar de Nacimiento</span>
                            <input type="text" value="Catamarca" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nacionalidad</span>
                            <input type="text" value="Argentino" required>
                        </div> 
                        <div class="select-box">
                            <span class="details">Formacion Profesional</span>
                            <select name="FP">
                                <option value="1">Informatica</option>
                                <option value="2">Herreria</option>
                                <option value="2">Peluqueria</option>
                                <option value="2" selected>Manualidades</option>
                                <option value="2">Electricidad</option>
                            </select>
                        </div>
                        <div class="select-box">
                            <span class="details">Escuelas</span>
                            <select name="escuela">
                                <option value="1" selected>EDJA N° 38</option>
                                <option value="2">EDJA N° 61</option>
                            </select>
                        </div>
                        <div class="select-box">
                            <span class="details">Tipo</span>
                            <select name="tipo">
                                <option value="1">Docente</option>
                                <option value="2" selected>Director</option>
                            </select required>
                        </div>
                        <div class="input-box">
                            <span class="details">Correo Electronico</span>
                            <input type="email" value="may@segui.com" required>
                        </div>                       
                    </div>
                </form>
            </div>
        </div>   
            
        <ul class="botones">
            <li class="boton-cancelar"><a href="director.html"> Cancelar</a></li>
            <li class="boton-aceptar"><a href="director.html"> Guardar Cambios</a></li>
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