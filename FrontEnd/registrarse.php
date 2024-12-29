<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="CSS/registrarse.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Estilos de Mapbox -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.css" rel="stylesheet" />
    <!-- Script de Mapbox -->
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.8.1/mapbox-gl.js"></script>
</head>

<!-- HEADER -->
<?php include('header.php'); ?>

<body class="body">
    <?php
    $c = 0; // Variable de control de errores

    if (isset($_POST['aceptar'])) 
    {
        // Datos del formulario
        $personas_id = $_POST['personas_id'];
        $personas_cuil = $_POST['personas_cuil'];
        $personas_apellido = strtoupper($_POST['personas_apellido']);
        $personas_nombre = strtoupper($_POST['personas_nombre']);
        $personas_fechnac = $_POST['personas_fechnac'];
        $usuarios_email = $_POST['usuarios_email'];
        $usuarios_clave = $_POST['usuarios_clave'];
        $telefonos_numero = $_POST['telefonos_numero'];
        $domicilios_calle = $_POST['domicilios_calle'];
        $domicilios_latitud = $_POST['domicilios_latitud'];
        $domicilios_longitud = $_POST['domicilios_longitud'];
        $escuelas_id = $_POST['escuelas_id'];
        $formaciones_profesionales_id = $_POST['formaciones_profesionales_id'];
        $tipos_personas = $_POST['tipos_personas'];
       
        //VALIDACIONES
        if (trim($personas_cuil) == '') {
            $c = 1;
            $error1 = "Debe ingresar un cuil";
        } else {
            if (!is_numeric($personas_cuil)) {
                $c = 1;
                $error1 = "Ingrese un valor numerico";
            }
        }

        if (trim($personas_apellido) == '') {
            $c = 1;
            $error2 = "Debe ingresar un apellido";
        }

        if (trim($personas_nombre) == '') {
            $c = 1;
            $error3 = "Debe ingresar un nombre";
        }

        if (trim($personas_fechnac) == '') {
            $c = 1;
            $error4 = "Debe ingresar una fecha";
        }

        if (trim($usuarios_email) == '') {
            $c = 1;
            $error5 = "Debe ingresar un correo";
        }

        if (trim($usuarios_clave) == '') {
            $c = 1;
            $error6 = "Debe ingresar una contraseña";
        }

        if (trim($telefonos_numero) == '') {
            $c = 1;
            $error7 = "Debe ingresar un telefono";
        } else {
            if (!is_numeric($telefonos_numero)) {
                $c = 1;
                $error7 = "Ingrese un valor numerico";
            }
        }

        if (trim($domicilios_calle) == '') {
            $c = 1;
            $error8 = "Debe ingresar un domicilio";
        }

        if (trim($escuelas_id) == '') {
            $c = 1;
            $error9 = "Debe seleccionar una escuela";
        }

        if (trim($formaciones_profesionales_id) == '') {
            $c = 1;
            $error10 = "Debe seleccionar una formacion profesional";
        }

        if (trim($tipos_personas) == '') {
            $c = 1;
            $error11 = "Debe seleccionar un si es Docente o Director";
        }


        //INICIO DEL CODIGO AGREGADO//
        if ($c == 0) 
        {
            $sql_personas = "INSERT INTO personas(personas_cuil, personas_apellido, personas_nombre, personas_fechnac) VALUES ('$personas_cuil', '$personas_apellido', '$personas_nombre', '$personas_fechnac')";
            $result1 = mysqli_query($conexion, $sql_personas);
            $ultimo_id_personas = mysqli_insert_id($conexion);

            $sql_usuarios = "INSERT INTO usuarios(usuarios_email, usuarios_clave, personas_id) VALUES ('$usuarios_email', '$usuarios_clave', '$ultimo_id_personas')";
            $result2 = mysqli_query($conexion, $sql_usuarios);

            $sql_telefonos = "INSERT INTO telefonos(telefonos_numero, personas_id) VALUES ('$telefonos_numero', '$ultimo_id_personas')";
            $result3 = mysqli_query($conexion, $sql_telefonos);

            $sql_domicilios = "INSERT INTO domicilios(domicilios_calle, personas_id) VALUES ('$domicilios_calle', '$ultimo_id_personas')";
            $result4 = mysqli_query($conexion, $sql_domicilios);

            $sql_es_fp_per = "INSERT INTO es_fp_per(escuelas_id, formaciones_profesionales_id, tipos_personas, personas_id) VALUES ('$escuelas_id', '$formaciones_profesionales_id', '$tipos_personas', '$ultimo_id_personas')";
            $result5 = mysqli_query($conexion, $sql_es_fp_per);

            if (mysqli_errno($conexion)==0)
            {
                ?>
                <script>
                alert('Se cargo correctamente los datos');
                </script>
                <?php
            }
            else
            {
                echo "No se Cargo correctamente <br>";
            }
        }
    }
        

    if (!isset($_POST['aceptar']) or $c != 0) 
    {
    ?>
        <main class="cuerpo">
            <div class="container">
                <div class="title">Registro de Usuario</div>
                <div class="content">

                    <form id="form" name="form" method="post">
                        <!-- Formulario 1 -->
                         <h6>Datos de Acceso</h6>
                        <div class="divider"></div>

                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Correo Electrónico</span>
                                    <input type="email" name="usuarios_email" id="usuarios_email" placeholder="Ingrese su correo electrónico" value="<?php echo $_POST['usuarios_email']; ?>">
                                    <?php
                                    if (isset($error1)) {
                                        echo $error1;
                                    }
                                    ?>
                                    <div id="error-correo" class="error-message"></div>
                                </div>
                            </div>

                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Contraseña</span>
                                    <input type="password" name="usuarios_clave" id="usuarios_clave" placeholder="Ingrese su contraseña" value="<?php echo $_POST['usuarios_clave']; ?>">
                                    <?php
                                    if (isset($error2)) {
                                        echo $error2;
                                    }
                                    ?>
                                    <div id="error-password" class="error-message"></div>
                                </div>

                                <div class="input-box">
                                    <span class="details">Repetir Contraseña</span>
                                    <input type="password" name="password_repetir" placeholder="Repita su contraseña">
                                    <div id="error-password-repetir" class="error-message"></div>
                                </div>
                            </div>
                        </div>


                        <!-- Formulario 2 -->
                        <h6>Datos Personales</h6>
                        <div class="divider"></div>

                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">CUIL</span>
                                    <input type="text" name="personas_cuil" id="personas_cuil" placeholder="Ingrese su CUIL" value="<?php echo $_POST['personas_cuil']; ?>">
                                    <div id="error-cuil" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Apellidos</span>
                                    <input type="text" name="personas_apellido" id="personas_apellido" placeholder="Ingrese sus Apellidos" value="<?php echo $_POST['personas_apellido']; ?>">
                                    <div id="error-apellido" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Nombres</span>
                                    <input type="text" name="personas_nombre" id="personas_nombre" placeholder="Ingrese sus Nombres" value="<?php echo $_POST['personas_nombre']; ?>">
                                    <div id="error-nombre" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Fecha de Nacimiento</span>
                                    <input type="date" name="personas_fechnac" id="personas_fechnac" value="<?php echo $_POST['personas_fechnac']; ?>">
                                    <div id="error-fecha" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Edad</span>
                                    <label type="number" name="edad" id="edad" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Dirección</span>
                                    <input type="text" name="domicilios_calle" id="domicilios_calle" placeholder="Ingrese su Dirección" value="<?php echo $_POST['domicilios_calle']; ?>" required>
                                    <div id="error-direccion" class="error-message"></div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Teléfono</span>
                                    <input type="tel" name="telefonos_numero" id="telefonos_numero" placeholder="Ingrese su Teléfono" value="<?php echo $_POST['telefonos_numero']; ?>" required>
                                    <div id="error-telefono" class="error-message"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Mapa -->
                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Mapa</span>
                                    <div id="map"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario 3 -->
                        <h6>Datos Institucionales</h6>
                        <div class="divider"></div>

                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Escuela</span>
                                    <select name="escuelas_id" required>
                                        <option value="">Seleccione una Escuela</option>
                                        <?php
                                        $sql_escuelas = "SELECT * FROM escuelas";
                                        $result_escuelas = mysqli_query($conexion, $sql_escuelas);

                                        while ($row_escuela = mysqli_fetch_assoc($result_escuelas)) {
                                            $selected = ($row_escuela['escuelas_id'] ==($_POST['escuelas_id']??''))?'selected':'';
                                            echo '<option value="'.$row_escuela['escuelas_id'].'"'.$selected.'>'.$row_escuela['escuelas_nombre'].'</option>';
                                        }
                                        ?>
                                    </select>
                                    <!-- PARTE NUEVA -->
                                    <div id="error-escuela" class="error-message"></div>
                                    <!-- HASTA AQUI -->
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Formación Profesional</span>
                                    <select name="formaciones_profesionales_id" required>
                                        <option value="">Seleccione una FP</option>
                                        <?php
                                        $sql_fp = "SELECT * FROM formaciones_profesionales";
                                        $result_fp = mysqli_query($conexion, $sql_fp);

                                        while ($row_fp = mysqli_fetch_assoc($result_fp)) {
                                            $selected = ($row_fp['formaciones_profesionales_id'] ==($_POST['formaciones_profesionales_id']??''))?'selected':'';
                                            echo '<option value="'.$row_fp['formaciones_profesionales_id'].'"'.$selected.'>'.$row_fp['formaciones_profesionales_nombre'].'</option>';
                                        }

                                        ?>
                                    </select>
                                    <!-- PARTE NUEVA -->
                                    <div id="error-formacion" class="error-message"></div>
                                    <!-- HASTA AQUI -->
                                </div>
                            </div>

                            <div class="col">
                                <div class="input-box">
                                    <span class="details">Tipo</span>
                                    <select name="tipos_personas" required>
                                        <option value="">Seleccione una opción</option>
                                        <option value="Docente">Docente</option>
                                        <option value="Director">Director</option>

                                    </select>
                                    <!-- PARTE NUEVA -->
                                    <div id="error-tipo" class="error-message"></div>
                                    <!-- HASTA AQUI -->
                                </div>
                            </div>
                        </div>


                        <div class="botones">
                            <button type="submit" name="aceptar" id="aceptar">Aceptar</button>
                            <button type="submit" name="cancelar">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    <?php
    }
    ?>

<script src="../BackEnd/registrarse.js"></script>

</body>

<!-- FOOTER -->
 <!-- FOOTER -->
<?php include('footer.php'); ?>


</html>