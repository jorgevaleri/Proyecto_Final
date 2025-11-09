<!DOCTYPE html>
<html lang="en">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/index.css">

<!-- HEADER -->
<?php include('header.php'); ?>

<body class="body">
    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA -->
    <main class="cuerpo">
        <!-- INTRODUCCION -->
        <section class="intro-cta">
            <h3 class="title">Calculadora para Registro de Asistencia</h3>

            <!-- CONTENEDOR PARA PARRAFO -->
            <div class="intro-paragraphs">
                <!-- PARRAFO 1 -->
                <p>
                    Esta calculadora te permite registrar las asistencias e inasistencias de tus alumnos de forma rápida
                    y obtener estadísticas útiles al instante. Cada alumno se carga en una fila: seleccioná su sexo y
                    escribí el total de asistencias e inasistencias. En el recuadro de la derecha se mostrará la cantidad
                    de días hábiles (se calcula automáticamente desde la primera fila). Si necesitás agregar más filas,
                    usá el botón <strong>"Agregar fila"</strong> que se encuentra más abajo.
                </p>

                <!-- PARRAFO 2 -->
                <p>
                    <strong>Beneficios de registrarse</strong><br>
                    - <strong>Guardar tus datos:</strong> Al registrarte podrás almacenar tus registros y volver a consultarlos.<br>
                    - <strong>Acceso desde cualquier dispositivo:</strong> Inicia sesión y sigue trabajando desde otra computadora.<br>
                    - <strong>Historial y reportes:</strong> Revisa las mediciones pasadas sin perder información.<br>
                    - <strong>Mayor seguridad:</strong> Tus datos quedan asociados a tu cuenta y no se pierden si cierras el navegador.
                </p>
            </div>
        </section>

        <!-- BOTONES AGREGAR FILA / VACIAR TABLA -->
        <div class="botones">
            <input type="button" onclick="agregarFila('id_tabla1')" value="Agregar fila">
            <input type="button" onclick="vaciarTabla('id_tabla1')" value="Vaciar tabla">
        </div>

        <!-- CONENEDOR PARA VALIDACION -->
        <div>

        </div>

        <!-- TABLAS -->
        <div class="tablas-container">

            <!-- TABLA 1 - ASISTENCIAS E INASISTENCIAS -->
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
                    <td>
                        <select required>
                            <option value="">Seleccione</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                        </select>
                    </td>
                    <td><input type="number" size="5" style="width: 80%; text-align: center;" min="0"></td>
                    <td><input type="number" size="5" style="width: 80%; text-align: center;" min="0"></td>
                    <td></td>
                </tr>
            </table>

            <!-- TABLA 2 - RESULTADOS -->
            <table id="id_tabla2" class="tabla2">
                <tr>
                    <td colspan="3">
                        <input type="number" id="id_dias_habiles" min="1" max="30" style="width: 80%; text-align: center;">
                    </td>
                    <th colspan="2">DIAS HABILES</th>
                </tr>
                <tr>
                    <th>VARONES</th>
                    <th>MUJERES</th>
                    <th>TOTAL</th>
                    <th colspan="2"></th>
                </tr>
                <tr>
                    <td id="id_asi_var"></td>
                    <td id="id_asi_muj"></td>
                    <td id="id_asi_tot"></td>
                    <th>ASISTENCIA</th>
                    <th rowspan="2">TOTAL</th>
                </tr>
                <tr>
                    <td id="id_ina_var"></td>
                    <td id="id_ina_muj"></td>
                    <td id="id_ina_tot"></td>
                    <th>INASISTENCIA</th>
                </tr>
                <tr>
                    <td id="id_asi_med_var"></td>
                    <td id="id_asi_med_muj"></td>
                    <td id="id_asi_med_tot"></td>
                    <th colspan="2">ASISTENCIA MEDIA</th>
                </tr>
                <tr>
                    <td id="id_por_var"></td>
                    <td id="id_por_muj"></td>
                    <td id="id_por_tot"></td>
                    <th colspan="2">% DE ASISTENCIA</th>
                </tr>
            </table>
        </div>
    </main>

    <!-- SCRIPTS -->
    <!-- VALIDACIONES -->
    <script src="JS/validaciones_globales.js" defer></script>
    <!-- CALCULOS -->
    <script src="JS/calculadora_asistencia.js" defer></script>

</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>