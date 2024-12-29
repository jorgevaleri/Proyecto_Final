<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro de Asistencia de Alumnos</title>

    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">

    <link rel="stylesheet" href="CSS/index.css">
</head>

<!-- HEADER -->
<?php include('header.php'); ?>

<body class="body">
    <main class="cuerpo">
        <h3 class="title">Calculadora para Registro de Asistencia</h3>
        <p>En esta pagina usted podra cargar sus alumnos (1 por fila), marcando el sexo y escribiendo el total de Asistencia e Inasistencia, en el cuadro de la derecha lo unico que debemos escribir es la cantidad de dias habiles, de esta forma el programa automaticamente realizara los calculos necesarios.<br>
            En caso de necesitar agregar mas filas, presione el boton "Agregar mas alumnos" que se encuentra en la parte inferior.
        </p>

        <div class="botones">
            <input type="button" onclick="insertarFila('id_tabla1')" value="Agregar fila">
            <input type="button" onclick="limpiarTabla('id_tabla1')" value="Vaciar tabla">
        </div>

        <table id="id_tabla1" class="tabla1" style="float: left;" width="50%">
            <tr>
                <th>N°</th>
                <th>SEXO</th>
                <th>TOTAL DE ASISTENCIAS</th>
                <th>TOTAL DE INASISTENCIAS</th>
                <th>ACCION</th>
            </tr>
            <tr>
                <td>1</td>
                <td><select required>
                        <option value="">Seleccione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                      </select></td>
                <td><input type="number" size="5" style="width: 80%; text-align: center;" min="0"></td>
                <td><input type="number" size="5" style="width: 80%; text-align: center;" min="0"></td>
                <td></td>
            </tr>
        </table>

        <table id="id_tabla2" class="tabla2" width="50%">
            <tr>
                <td colspan="3">
                    <input type="number" id="id_dias_habiles" min="1" max="30" style="width: 80%; text-align: center;">
                </td>
                <th colspan="2">DIAS HABILES</td>
            </tr>
            <tr>
                <th>VARONES</th>
                <th>MUJERES</th>
                <th>TOTAL</td>
                <th colspan="2"></td>
            </tr>
            <tr>
                <td id="id_asi_var"></td>
                <td id="id_asi_muj"></td>
                <td id="id_asi_tot"></td>
                <th>ASISTENCIA</td>
                <th rowspan="2">TOTAL</td>
            </tr>
            <tr>
                <td id="id_ina_var"></td>
                <td id="id_ina_muj"></td>
                <td id="id_ina_tot"></td>
                <th>INASISTENCIA</td>
            </tr>
            <tr>
                <td id="id_asi_med_var"></td>
                <td id="id_asi_med_muj"></td>
                <td id="id_asi_med_tot"></td>
                <th colspan="2">ASISTENCIA MEDIA</td>
            </tr>
            <tr>
                <td id="id_por_var"></td>
                <td id="id_por_muj"></td>
                <td id="id_por_tot"></td>
                <th colspan="2">% DE ASISTENCIA</td>
            </tr>
        </table>
    </main>

    <script src="../BackEnd/index.js"></script>
    <script src="https://kit.fontawesome.com/73731765b0.js" crossorigin="anonymous"></script>
</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>