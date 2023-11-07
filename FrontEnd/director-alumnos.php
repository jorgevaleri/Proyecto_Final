<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/docente-alumnos.css">
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
                        <li class="seleccion"><a href="director-resumen.html">Informatica</a></li>
                        <li class="sin-seleccion"><a href="#">Herreria</a></li>
                        <li class="sin-seleccion"><a href="#">Peluqueria</a></li>
                        <li class="seleccion"><a href="director-agregar-fp.html">Agregar F.P.</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </aside>



    <main class="cuerpo">
        <div class="menu-interno">
            <ul>
                <li><a href="director-resumen.html">Resumen</a></li>
                <li class="seleccion2"><a href="director-alumnos.html">Alumno</a></li>
            </ul>
        </div>

        <h3 class="title">Registro de Alumnos</h3>
        <p>En esta pagina usted podra observar los alumnos cargados (1 por fila), estos datos se exportan a la planilla Registro de forma automatica.<br></p>   

        <div class="anio">
            <h5>Año: </h5>
            <select style="width: 80%; text-align: center;">
                <option value="1">2020</option>
                <option value="2">2021</option>
                <option value="3" selected>2023</option>
                <option value="4">2024</option>
            </select>
        </div>

        <div class="tablas">
            <table class="tabla1">
                <tr class="tabla-encabezado">
                    <td width="150">DNI</td>
                    <td width="150">APELLIDOS</td>
                    <td width="150">NOMBRES</td>
                    <td width="150">FECHA DE NACIMIENTO</td>
                    <td width="60">EDAD</td>
                    <td width="60">SEXO</td>
                    <td width="150">TELEFONO</td>
                    <td width="60">ACCION</td>
                </tr>

                <tr class="tablatd-datos">
                    <td>36844411</td>
                    <td>Aranda</td>
                    <td>Georgina Elizabeth</td>
                    <td>1992-01-18</td>
                    <td>31</td>
                    <td>Femenino</td>
                    <td>3834651363</td>
                    <div class="botones-tabla">
                        <td>
                            <a href="director-ver-alumnos.html" class="bi bi-eye-fill"></a>
                        </td>
                    </div>                    
                </tr>
                <tr class="tablatd-datos">
                    <td>44118315</td>
                    <td>Barros</td>
                    <td>Cecilia Abigail</td>
                    <td>2002-08-29</td>
                    <td>30</td>
                    <td>Femenino</td>
                    <td>3834978540</td>
                    <div class="botones-tabla">
                        <td>
                            <a href="director-ver-alumnos.html" class="bi bi-eye-fill"></a>
                        </td>
                    </div>                    
                </tr>
                <tr class="tablatd-datos">
                    <td>44117238</td>
                    <td>Bazan</td>
                    <td>Franco Daniel</td>
                    <td>2002-06-26</td>
                    <td>21</td>
                    <td>Masculino</td>
                    <td>3834624851</td>
                    <div class="botones-tabla">
                        <td>
                            <a href="director-ver-alumnos.html" class="bi bi-eye-fill"></a>
                        </td>
                    </div>                    
                </tr>
                <tr class="tablatd-datos">
                    <td>47035739</td>
                    <td>Coronel</td>
                    <td>Evelin Melani</td>
                    <td>2005-03-16</td>
                    <td>18</td>
                    <td>Femenino</td>
                    <td>3834328031</td>
                    <div class="botones-tabla">
                        <td>
                            <a href="director-ver-alumnos.html" class="bi bi-eye-fill"></a>
                        </td>
                    </div>                    
                </tr>
                <tr class="tablatd-datos">
                    <td>45079912</td>
                    <td>Nieto</td>
                    <td>Yamile Veronica</td>
                    <td>2003-11-09</td>
                    <td>19</td>
                    <td>Femenino</td>
                    <td>3834978041</td>
                    <div class="botones-tabla">
                        <td>
                            <a href="director-ver-alumnos.html" class="bi bi-eye-fill"></a>
                        </td>
                    </div>                    
                </tr>
               
                
            </table>
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