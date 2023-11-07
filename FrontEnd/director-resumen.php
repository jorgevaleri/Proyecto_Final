<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/docente-resumen.css">
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
                <li class="seleccion2"><a href="director-resumen.html">Resumen</a></li>
                <li><a href="director-alumnos.html">Alumno</a></li>
            </ul>
        </div>

        <h3 class="title">Resumen Anual</h3>
        

        <div class="anio">
            <h5>Año: </h5>
            <select style="width: 80%; text-align: center;">
                <option value="1">2020</option>
                <option value="2">2021</option>
                <option value="3" selected>2023</option>
                <option value="4">2024</option>
            </select>
        </div>

        <h3 class="title">Inasistencia mensual del alumno</h3>
        <p>En esta tabla podemos observar un resumen anual de las inasistencia que tuvo cada alumno (1 por fila)</p>
        <div class="tablas">
            <table class="tabla1">
                <tr class="tabla-encabezado">
                    <td rowspan="2" width="150px">DNI</td>
                    <td colspan="10">INASISTENCIAS TOTAL POR MES</td>
                    <td rowspan="2" width="80px">TOTAL</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td width="70">MAR.</td>
                    <td width="70">ABR.</td>
                    <td width="70">MAY.</td>
                    <td width="70">JUN.</td>
                    <td width="70">JUL.</td>
                    <td width="70">AGO.</td>
                    <td width="70">SEP.</td>
                    <td width="70">OCT.</td>
                    <td width="70">NOV.</td>
                    <td width="70">DIC.</td>
                </tr>
                <div>
                    <tr class="tablatd">
                        <td>36844411</td>
                        <td>11</td>
                        <td>10</td>
                        <td>8</td>
                        <td>11</td>
                        <td>2</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">42</td>
                    </tr>
                    <tr class="tablatd">
                        <td>44118315</td>
                        <td>8</td>
                        <td>5</td>
                        <td>6</td>
                        <td>6</td>
                        <td>4</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">29</td>
                    </tr>
                    <tr class="tablatd">
                        <td>44117238</td>
                        <td>6</td>
                        <td>4</td>
                        <td>8</td>
                        <td>9</td>
                        <td>1</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">28</td>
                    </tr>
                    <tr class="tablatd">
                        <td>47035739</td>
                        <td>3</td>
                        <td>0</td>
                        <td>0</td>
                        <td>6</td>
                        <td>0</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">9</td>
                    </tr>
                    <tr class="tablatd">
                        <td>45079912</td>
                        <td>13</td>
                        <td>14</td>
                        <td>7</td>
                        <td>7</td>
                        <td>4</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-weight: bold;">45</td>
                    </tr>
                    
                    <tr class="tabla-encabezado">
                        <td style="height: 45px;">TOTAL</td>
                        <td>41</td>
                        <td>33</td>
                        <td>29</td>
                        <td>39</td>
                        <td>11</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </div>
            </table>

            <br>
            <br>
            <h3 class="title">Asistencia de la Formacion Profesional</h3>
            <p>En esta tabla podemos observar un resumen anual de las asistencias, inasistencia, asistencia media y porcentaje de asistencia que se registro en la formacion profesional</p>

            <table class="tabla2">
                <tr class="tabla-encabezado">
                    <td rowspan="2" width="100"></td>
                    <td colspan="3">ASISTENCIA</td>
                    <td colspan="3">INASISTENCIA</td>
                    <td colspan="3">ASISTENCIA MEDIA</td>
                    <td colspan="3">% DE ASISTENCIA</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td width="70">V</td>
                    <td width="70">M</td>
                    <td width="70">T</td>
                    <td width="70">V</td>
                    <td width="70">M</td>
                    <td width="70">T</td>
                    <td width="70">V</td>
                    <td width="70">M</td>
                    <td width="70">T</td>
                    <td width="70">V</td>
                    <td width="70">M</td>
                    <td width="70">T</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">MAR.</td>
                    <td>12</td>
                    <td>37</td>
                    <td>49</td>
                    <td>6</td>
                    <td>35</td>
                    <td>41</td>
                    <td>1</td>
                    <td>2</td>
                    <td>3</td>
                    <td>67%</td>
                    <td>51%</td>
                    <td>54%</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">ABR.</td>
                    <td>10</td>
                    <td>27</td>
                    <td>37</td>
                    <td>4</td>
                    <td>29</td>
                    <td>33</td>
                    <td>1</td>
                    <td>2</td>
                    <td>3</td>
                    <td>71%</td>
                    <td>48%</td>
                    <td>53%</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">MAY.</td>
                    <td>5</td>
                    <td>31</td>
                    <td>36</td>
                    <td>8</td>
                    <td>21</td>
                    <td>29</td>
                    <td>0</td>
                    <td>2</td>
                    <td>3</td>
                    <td>38%</td>
                    <td>60%</td>
                    <td>55%</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">JUN.</td>
                    <td>3</td>
                    <td>18</td>
                    <td>21</td>
                    <td>9</td>
                    <td>30</td>
                    <td>39</td>
                    <td>0</td>
                    <td>2</td>
                    <td>2</td>
                    <td>25%</td>
                    <td>38%</td>
                    <td>35%</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">JUL.</td>
                    <td>8</td>
                    <td>26</td>
                    <td>34</td>
                    <td>1</td>
                    <td>10</td>
                    <td>11</td>
                    <td>1</td>
                    <td>3</td>
                    <td>4</td>
                    <td>89%</td>
                    <td>72%</td>
                    <td>76%</td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">AGO.</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">SEP.</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">OCT.</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">NOV.</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="tablatd">
                    <td style="font-weight: bold;">DIC.</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>TOTAL</td>
                    <td>38</td>
                    <td>139</td>
                    <td>177</td>
                    <td>28</td>
                    <td>125</td>
                    <td>153</td>
                    <td>3</td>
                    <td>11</td>
                    <td>14</td>
                    <td>290%</td>
                    <td>269%</td>
                    <td>273%</td>
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