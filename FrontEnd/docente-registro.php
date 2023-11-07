<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/docente-registro.css">
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
                        <li class="seleccion"><a href="#">EDJA N° 38</a></li>
                        <li class="sin-seleccion"><a href="#">Escuela 2</a></li>
                        <li class="sin-seleccion"><a href="#">Escuela 3</a></li>
                        <li><a href="agregar-escuela.html">Agregar Escuela</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </aside>



    <main class="cuerpo">
        <div class="menu-interno">
            <ul>
                <li class="seleccion2"><a href="docente-registro.html">Registro</a></li>
                <li><a href="docente-resumen.html">Resumen</a></li>
                <li><a href="docente-alumnos.html">Alumno</a></li>
            </ul>
        </div>

        <div class="tablas">
            <h3 class="title">Calculadora para Registro de Asistencia</h3>
            <p>En esta pagina usted tendra cargado sus alumnos (1 por fila) con su respectivo sexo, solamente tendra que escribir el total de Asistencia e Inasistencia, en el cuadro de la derecha lo unico que debemos escribir es la cantidad de dias habiles, de esta forma el programa automaticamente realizara los calculos necesarios.<br></p>       
            <table class="tabla1" style="float: left;" width="50%">
                <tr class="tabla-encabezado">
                    <td>DNI</td>
                    <td>SEXO</td>
                    <td>ASISTENCIAS</td>
                    <td>INASISTENCIAS</td>
                </tr>
                <div>
                    <tr class="tablatd">
                        <td>36844411</td>
                        <td>Femenino</td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="7"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="2"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>44118315</td>
                        <td>Femenino</td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="5"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="4"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>44117238</td>
                        <td>Masculino</td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="8"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="1"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>47035739</td>
                        <td>Femenino</td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="9"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="0"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>45079912</td>
                        <td>Femenino</td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="5"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="4"></td>
                    </tr>
                </div>
            </table>


            <table class="tabla2" width="50%">
                <tr class="tabla-encabezado">
                    <td class="tablatd">
                        <select style="width: 80%; text-align: center;">
                            <option value="1">2020</option>
                            <option value="2">2021</option>
                            <option value="3" selected>2023</option>
                            <option value="4">2024</option>
                        </select>
                    </td>
                    <td>AÑO</td>
                    <td colspan="2" class="tablatd">
                        <select style="width: 80%; text-align: center;">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7" selected>Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </td>
                    <td>MES</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td colspan="3" class="tablatd">
                        <input type="number" min="1" max="30" style="width: 80%; text-align: center;" value="9">
                    </td>
                    <td colspan="2">DIAS HABILES</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td width="20%">VARONES</td>
                    <td width="20%">MUJERES</td>
                    <td width="20%">TOTAL</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>8</td>
                    <td>26</td>
                    <td>34</td>
                    <td>ASISTENCIA</td>
                    <td rowspan="2">TOTAL</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>1</td>
                    <td>10</td>
                    <td>11</td>
                    <td>INASISTENCIA</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>1</td>
                    <td>3</td>
                    <td>4</td>
                    <td colspan="2">ASISTENCIA MEDIA</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>89%</td>
                    <td>72%</td>
                    <td>76%</td>
                    <td colspan="2">% DE ASISTENCIA</td>
                </tr>
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