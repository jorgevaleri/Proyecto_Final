<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("../BackEnd/conexion.php"); ?>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Asistencia de Alumnos</title>
    <link rel="stylesheet" href="CSS/registrarse.css">
    <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<header>
    <div class="ancho">
        <div class="logo">
            <a href="index.php"><img src="Imagenes/Logo_3.png" width="300" height="75"></a>
        </div>

        <nav>
            <ul>
                <li><a href="index.php" class="bi bi-house-door-fill">  Inicio</a></li>
                <li><a href="logeo.php">Iniciar Session</a></li>
            </ul>
        </nav>
    </div>
</header>

<body class="body">
    <?php 
    $c=0;
	
	if(isset($_POST['enviar']))
	{
		$personas_apellido=strtoupper($_POST['personas_apellido']);
		$personas_nombre=strtoupper($_POST['personas_nombre']);
		$personas_dni =$_POST['personas_dni'];
		$personas_fechnac =$_POST['personas_fechnac'];
		$sexos_id =$_POST['sexos_id'];		
		$correos_denominacion =$_POST['correos_denominacion'];
		$personas_id=$_POST['personas_id'];
		$correos_id=$_POST['correos_id'];
		$usuarios_contraseña =$_POST['usuarios_contraseña'];
		$roles_id=$_POST['roles_id'];
		$estados_id=$_POST['estados_id'];
				
		if(trim($personas_apellido)=='')
		{
			$c=1;
			$error1="Debe ingresar un apellido";
		}
		
		if(trim($personas_nombre)=='')
		{
			$c=1;
			$error2="Debe ingresar un nombre";
		}

		if(trim($personas_dni)=='')
		{
			$c=1;
			$error3="Debe ingresar un dni";
		}
		else
		{
			if(!is_numeric($personas_dni))
			{
				$c=1;
				$error3="Ingrese un valor numerico";
			}
		}

		if(trim($personas_fechnac)=='')
		{
			$c=1;
			$error4="Debe ingresar una fecha";
		}

		if(trim($sexos_id)=='')
		{
			$c=1;
			$error5="Debe seleccionar un sexo";
		}
		
			
			
		if(trim($correos_denominacion)=='')
		{
			$c=1;
			$error7="Debe ingresar un correo";
		}

		if(trim($usuarios_contraseña)=='')
		{
			$c=1;
			$error8="Debe ingresar una contraseña";
		}

		if(trim($roles_id)=='')
		{
			$c=1;
			$error9="Debe seleccionar un rol";
		}

		if(trim($estados_id)=='')
		{
			$c=1;
			$error10="Debe seleccionar un estado";
		}
		
			
		if($c==0)
		{
			$dato= $_POST['personas_dni'];
			$sql3="SELECT * FROM personas WHERE personas_dni='$dato'";
			$verificar_Dato = mysqli_query($conexion, $sql3);
			$cant=mysqli_num_rows($verificar_Dato);

			if($cant > 0)
			{
				//Existe el dato
				?>
				<script>
					alert('El DNI YA EXISTE');
					location.href ='usuarios.php';
				</script>
				<?php
			}
			else
			{ 
				$dato2= $_POST['correos_denominacion'];
				$sql4="SELECT * FROM correos WHERE correos_denominacion='$dato2'";
				$verificar_Dato = mysqli_query($conexion, $sql4);
				$cant=mysqli_num_rows($verificar_Dato);

				if($cant > 0)
				{
					//Existe el dato
					?>
					<script>
						alert('El CORREO YA EXISTE');
						location.href ='usuarios.php';
					</script>
					<?php
				}
				else
				{
					//No existe el dato
					
					$sql_personas="INSERT INTO personas(personas_id, personas_apellido, personas_nombre, personas_dni, personas_fechnac, sexos_id) VALUES ('$personas_id', '$personas_apellido', '$personas_nombre', '$personas_dni', '$personas_fechnac', '$sexos_id')";
					$result1=mysqli_query($conexion,$sql_personas);
					$ultimo_id_personas = mysqli_insert_id($conexion);

					$sql_correos="INSERT INTO correos(correos_denominacion, personas_id) VALUES ('$correos_denominacion', '$ultimo_id_personas')";
					$result2=mysqli_query($conexion,$sql_correos);
					$ultimo_id_correos = mysqli_insert_id($conexion);

					$sql_usuarios="INSERT INTO usuarios(correos_id, usuarios_contraseña, personas_id, roles_id, estados_id) VALUES ('$ultimo_id_correos', '$usuarios_contraseña', '$ultimo_id_personas', '$roles_id', '$estados_id')";
					$result3=mysqli_query($conexion,$sql_usuarios);

					?>
					<script>
						location.href ='usuarios.php';
					</script>
					<?php
				}
			}
		}			

		if (mysqli_errno($conexion)==0)
		{
			?>
				
			<script >	
				location.href ='usuarios.php';
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


    <main class="cuerpo">
        <div class="container">
            <div class="title">Nuevo Registro</div>
            <div class="content">
                <form action="#">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">DNI</span>
                            <input type="text" placeholder="Ingrese su DNI o CUIL" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Apellidos</span>
                            <input type="text" placeholder="Ingrese su Apellido" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nombres</span>
                            <input type="text" placeholder="Ingrese su Nombre" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Fecha de Nacimiento</span>
                            <input type="date" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Edad</span>
                            <input type="text" placeholder="Ingrese su Edad" required>
                        </div>
                        <div class="select-box">
                            <span class="details">Sexo</span>
                            <select name="sexo">
                                <option value="1">Masculino</option>
                                <option value="2">Femenino</option>
                            </select required>
                        </div>
                        <div class="input-box">
                            <span class="details">Telefono</span>
                            <input type="text" placeholder="Ingrese su Telefono" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Direccion</span>
                            <input type="text" placeholder="Ingrese su Direccion" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Localidad</span>
                            <input type="text" placeholder="Ingrese su Localidad" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Lugar de Nacimiento</span>
                            <input type="text" placeholder="Ingrese la provincia donde Nacio" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Nacionalidad</span>
                            <input type="text" placeholder="Ingrese el pais donde Nacio" required>
                        </div>
                        <div class="select-box">
                            <span class="details">Formacion Profesional</span>
                            <select name="FP">
                                <option value="1">Informatica</option>
                                <option value="2">Herreria</option>
                                <option value="2">Peluqueria</option>
                                <option value="2">Manualidades</option>
                                <option value="2">Electricidad</option>
                            </select>
                        </div>
                        <div class="select-box">
                            <span class="details">Escuelas</span>
                            <select name="escuela">
                                <option value="1">EDJA N° 38</option>
                                <option value="2">EDJA N° 61</option>
                            </select>
                        </div>
                        <div class="select-box">
                            <span class="details">Tipo</span>
                            <select name="tipo">
                                <option value="1">Docente</option>
                                <option value="2">Director</option>
                            </select required>
                        </div>
                        <div class="input-box">
                            <span class="details">Correo Electronico</span>
                            <input type="email" placeholder="Ingrese su correo electronico" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Contraseña</span>
                            <input type="text" placeholder="Ingrese su contraseña" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Confirmar Contraseña</span>
                            <input type="text" placeholder="Repita su contraseña" required>
                        </div>
                    </div>
                    
                    <div class="botones">
                        <ul >
                            <li class="button-cancelar">
                                <a href="index.php">Cancelar</a>
                            </li>

                            <li class="button-registrarse">
                                <input onclick="inicio_sesion.php" type="submit" value="Registrarse">
                            </li>
                        </ul>   
                </form>
            </div>
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