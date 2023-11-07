<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../BackEnd/conexion.php"); ?>	

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/index.css">
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
                <a href="index.php"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
            </div>
    
            <nav>
                <ul>
                    <li><a href="logeo.php">Iniciar Sesion</a></li>
                    <li><a href="registrarse.php">Registrarse</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="cuerpo">
        <h3 class="title">Calculadora para Registro de Asistencia</h3>
        <p>En esta pagina usted podra cargar sus alumnos (1 por fila), marcando el sexo y escribiendo el total de Asistencia e Inasistencia, en el cuadro de la derecha lo unico que debemos escribir es la cantidad de dias habiles, de esta forma el programa automaticamente realizara los calculos necesarios.<br>
        En caso de necesitar agregar mas filas, presione el boton "Agregar mas alumnos" que se encuentra en la parte inferior.
        </p>       
        <table class="tabla1" style="float: left;" width="50%">
                <tr class="tabla-encabezado">
                    <td>N°</td>
                    <td>SEXO</td>
                    <td>TOTAL DE ASISTENCIAS</td>
                    <td>TOTAL DE INASISTENCIAS</td>
                </tr>
                <div>
                    <tr class="tablatd">
                        <td>1</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="7"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="11"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>2</td>
                        <td>
                            <select>
                                <option value="Masculino" selected>Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="10"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="8"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>3</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="12"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="6"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>4</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="15"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="3"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>5</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="5"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="13"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>6</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="14"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="4"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>7</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="6"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="12"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>8</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="9"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="9"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>9</td>
                        <td>
                            <select>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino" selected>Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="12"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="6"></td>
                    </tr>
                    <tr class="tablatd">
                        <td>10</td>
                        <td>
                            <select>
                                <option value="Masculino" selected>Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                        </td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="7"></td>
                        <td><input type="text" size="5" style="width: 80%; text-align: center;" value="11"></td>
                    </tr>
                    
                </div>               
            </table>     
              
            
            <table class="tabla2" width="50%">
                <tr class="tabla-encabezado">
                    <td colspan="3" class="tablatd">
                        <input type="number" min="1" max="30" style="width: 80%; text-align: center;" value="18">
                    </td>
                    <td colspan="2">DIAS HABILES</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>VARONES</td>
                    <td>MUJERES</td>
                    <td>TOTAL</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>17</td>
                    <td>80</td>
                    <td>97</td>
                    <td>ASISTENCIA</td>
                    <td rowspan="2">TOTAL</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>19</td>
                    <td>64</td>
                    <td>83</td>
                    <td>INASISTENCIA</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>1</td>
                    <td>4</td>
                    <td>5</td>
                    <td colspan="2">ASISTENCIA MEDIA</td>
                </tr>
                <tr class="tabla-encabezado">
                    <td>47%</td>
                    <td>56%</td>
                    <td>54%</td>
                    <td colspan="2">% DE ASISTENCIA</td>
                </tr>                
            </table>

            <div class="boton-agregar">
                <a href="#"> Agregar mas alumnos</a>
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