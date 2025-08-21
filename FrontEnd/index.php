<!DOCTYPE html>
<html lang="en">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- ESTILOS CSS -->
 <link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_public.css">

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

        <div class="tablas-container">
        <table id="id_tabla1" class="tabla1">
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

        <table id="id_tabla2" class="tabla2">
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
        </div>
    </main>

    <script src="../BackEnd/index.js"></script>
    <script src="https://kit.fontawesome.com/73731765b0.js" crossorigin="anonymous"></script>
</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>