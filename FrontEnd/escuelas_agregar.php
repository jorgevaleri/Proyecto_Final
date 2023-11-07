<!DOCTYPE html>
<html lang="en">

<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/agregar-escuela.css">
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
                    <li><a href="perfil.php"><?php echo $_SESSION['personas_nombre']; ?></a></li>
                    <li><a href="deslogeo.php">Cerrar Sesion</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <aside class="cuerpo-menu-vertical">
        <div class="contenedor-menu-vertical">
            <ul class="menu-vertical">
                <li><a href="#">Escuelas</a>
                    <?php
                    $c=0;
                    $sql1="SELECT escuelas_id, escuelas_nombre FROM escuelas";
                    $resul1=mysqli_query($conexion,$sql1);
                    ?>

                    <ul class="submenu-vertical">

                        <?php
                        $cant=mysqli_num_rows($resul1);
                        for($i=0; $i<$cant; $i++)
                        {
                            $reg=mysqli_fetch_row($resul1);
                            ?>

                            <li class="sin-seleccion"><a href="escuelas.php?tipo=1&id=<?php echo $reg[0]; ?>"><?php echo $reg[1];?></a></li>
                            <?php
                        }
                        ?>
                <li class="seleccion"><a href="escuelas_agregar.php">Agregar Escuela</a></li>
            </ul>
            </li>
                <br>
                <li><a href="#">Director</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-docente.php">Mayra Segui</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Docentes</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-docente.php">Jorge Valeri</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 2</a></li>
                        <li class="sin-seleccion"><a href="#">Docente 3</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Formaciones Profesionales</a>
                    <ul class="submenu-vertical">
                        <li class="sin-seleccion"><a href="director-resumen.php">Informatica</a></li>
                        <li class="sin-seleccion"><a href="#">Herreria</a></li>
                        <li class="sin-seleccion"><a href="#">Peluqueria</a></li>
                        <li class="seleccion"><a href="director-agregar-fp.php">Agregar F.P.</a></li>
                    </ul>
                </li>
                <br>
                <li><a href="#">Permisos</a></li>
                <br>
                <li><a href="#">Auditorias</a></li>
            </ul>
        </div>
    </aside>

    <main class="cuerpo">
    <?php

	$c=0;
	
	if(isset($_POST['enviar']))
	{
		$escuelas_nombre=strtoupper($_POST['escuelas_nombre']);
		$escuelas_cue =$_POST['escuelas_cue'];
		$domicilios_id =$_POST['domicilios_id'];
        $domicilios_calle=strtoupper($_POST['domicilios_calle']);
		$domicilios_altura =$_POST['domicilios_altura'];		
		$domicilios_tipo =$_POST['domicilios_tipo'];
        $departamentos_id=$_POST['departamentos_id'];
		$localidades_id=$_POST['localidades_id'];
		$domicilios_descripcion=$_POST['domicilios_descripcion'];
				
		if(trim($escuelas_nombre)=='')
		{
			$c=1;
			$error1="Debe ingresar un nombre de la escuela";
		}
		
	    if(trim($escuelas_cue)=='')
		{
			$c=1;
			$error2="Debe ingresar un cue de la escuela";
		}
		else
		{
			if(!is_numeric($escuelas_cue))
			{
				$c=1;
				$error2="Ingrese un valor numerico";
			}
		}

		if(trim($domicilios_calle)=='')
		{
			$c=1;
			$error3="Debe ingresar una calle";
		}

        if(trim($domicilios_altura)=='')
		{
			$c=1;
			$error4="Debe ingresar una altura";
		}
		else
		{
			if(!is_numeric($domicilios_altura))
			{
				$c=1;
				$error4="Ingrese un valor numerico";
			}
		}

        if(trim($departamentos_id)=='')
		{
			$c=1;
			$error5="Debe ingresar un departamento";
		}

		if(trim($localidades_id)=='')
		{
			$c=1;
			$error6="Debe ingresar una localidad";
		}

		if(trim($domicilios_descripcion)=='')
		{
			$c=1;
			$error7="Debe ingresar una descripcion";
		}

		if($c==0)
		{
			$dato= $_POST['escuelas_cue'];
			$sql3="SELECT * FROM escuelas WHERE escuelas_cue='$dato'";
			$verificar_Dato = mysqli_query($conexion, $sql3);
			$cant=mysqli_num_rows($verificar_Dato);

			if($cant > 0)
			{
				//Existe el dato
				?>
				<script>
					alert('El CUE YA EXISTE');
					location.href ='menu_principal.php';
				</script>
				<?php
			}
			else
			{
                //No existe el dato

                $sql_domicilios="INSERT INTO domicilios(domicilios_calle, domicilios_altura, domicilios_tipo, localidades_id, domicilios_descripcion) VALUES ('$domicilios_calle', '$domicilios_altura', '$domicilios_tipo', '$localidades_id', '$domicilios_descripcion')";
                $result2=mysqli_query($conexion,$sql_domicilios);
                $ultimo_id_domicilios = mysqli_insert_id($conexion);
                
                $sql_escuelas="INSERT INTO escuelas(escuelas_nombre, escuelas_cue, ultimo_id_domicilios) VALUES ('$escuelas_nombre', '$escuelas_cue', '$personas_nombre', '$ultimo_id_domicilios')";
                $result1=mysqli_query($conexion,$sql_escuelas);

                ?>
                <script>
                    location.href ='menu_principal.php';
                </script>
                <?php
			
			}
		}			

		if (mysqli_errno($conexion)==0)
		{
			?>
				
			<script >	
				location.href ='menu_principal.php';
			</script>	

			<?php
					
		}
		else
		{
			echo "No se Cargo correctamente<br>";
		}
					
		?>				
		<?php
	}
    ?>
        <div class="container">
            <div class="title">Agregar Nueva Escuela</div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">Nombre</span>
                            <input name="escuelas_nombre" type="text" id="escuelas_nombre" value="<?php echo $_POST['escuelas_nombre']; ?>" size="30" maxlength="60" placeholder="Ingrese el nombre de la escuela" required>
							<?php
							
							if(isset($error1))
							{
								echo $error1;
							}
							?>
                        </div>

                        <div class="input-box">
                            <span class="details">CUE</span>
                            <input name="escuelas_cue" type="text" id="escuelas_cue" size="15" maxlength="20" value="<?php echo $_POST['escuelas_cue']; ?>" placeholder="Ingrese el cue de la escuela" required>
					 		<?php
							
							if(isset($error2))
							{
								echo $error2;
							}
                            ?>
                        </div>

                        <div class="input-box">
                            <span class="details">Calle</span>
                            <input name="domicilios_calle" type="text" id="domicilios_calle" size="15" maxlength="20" value="<?php echo $_POST['domicilios_calle']; ?>" placeholder="Ingrese la calle de la escuela" required>
					 		<?php
							
							if(isset($error3))
							{
								echo $error3;
							}
                            ?>
                        </div>

                        <div class="input-box">
                            <span class="details">Altura</span>
                            <input name="domicilios_altura" type="text" id="domicilios_altura" size="15" maxlength="20" value="<?php echo $_POST['domicilios_altura']; ?>" placeholder="Ingrese la altura de la escuela" required>
					 		<?php
							
							if(isset($error4))
							{
								echo $error4;
							}
                            ?>
                        </div>

                        <div class="input-box">
                            <span class="details">Tipo</span>
                            <input name="domicilios_tipo" type="text" id="domicilios_tipo" size="15" maxlength="20" value="Escuela" readonly>
                        </div>

                        <div class="input-box">
                            <span class="details">Provincia</span>
                            <input value="Catamarca" readonly>
                        </div>

                        <div class="input-box">
                            <span class="details">Departamentos</span>
                            <select name="departamentos" id="departamentos">
								<option value="0">Seleccione:</option>
								<?php 
								$sql_departamentos="SELECT * FROM departamentos WHERE provincias_id='10' INNER JOIN localidades ON departamentos.localidades_id=localidades.localidades_id";
								$result_departamentos=mysqli_query($conexion,$sql_departamentos);
								
								while ($valores = mysqli_fetch_array($result_departamentos))
								{
									echo '<option value="'.$valores['departamentos_id'].'">'.$valores['departamentos_nombre'].'</option>';
								}
								?>
							</select>
							<?php
				
							if(isset($error5))
							{
								echo $error5;
							}	
							?>
                        </div>

                        <div class="input-box">
                            <span class="details">Localidades</span>
                            <select name="localidades" id="localidades">
                            <option value="0">Seleccione:</option>
								
							</select>
							<?php
				
							if(isset($error6))
							{
								echo $error6;
							}	
							?>
                        </div>

                        <!-- FALTA -->
                        <div>
                            <!-- agregar mapa que envie latitud y longitud -->
                        </div>
                        <!-- FALTA -->
                    
                    <div>
                        <div class="boton-agregar">
                            <input type="submit" name="enviar" id="enviar" value="Agregar Escuela">
                        </div>
                    </div>
                </form>
            </div>
        </div>   
    </main>
</body>

<script type="application/javascript">
    const local = Array();
    <?php
    foreach($valores as $valor) {
        $local = array_values(array_filter($data, function($row) use ($countryId) {
            return $row['CountryID'] === $countryId;
        } ));
        ?>
    local[<?php echo $countryId;?>] = [ <?php
        for ($i = 0; $i < count($local) - 1; $i++ ) {
            ?>{ id: <?php echo $local[$i]['CityID']; ?>, name: "<?php echo $local[$i]['CityName']; ?>" }, <?php
        }
        ?>{ id: <?php echo $local[$i]['CityID']; ?>, name: "<?php echo $local[$i]['CityName']; ?>" } ];
    <?php
    }
    ?>
</script>

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