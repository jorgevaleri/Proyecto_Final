<head>
<?php
// // head.php
// // ESTE ARCHIVO NO DEBE ENVIAR NADA ANTES DE ESTE BLOQUE
// // Incluir conexión y helpers ANTES de cualquier salida HTML
// require_once __DIR__ . '/../BackEnd/conexion.php';
// require_once __DIR__ . '/auth_helpers.php'; // ajustá la ruta según tu estructura

// CONEXION A BASE DE DATOS
    include("../BackEnd/conexion.php"); 
    
    // AUTENTICACION SEGUN ROLES
    require_once __DIR__ . '/auth_helpers.php';
?>

  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="Imagenes/Logo_2.jpg" type="image/x-icon">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" 
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600&family=Open+Sans:wght@400;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css">
  <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.js"></script>
  <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.1/mapbox-gl.css" rel="stylesheet">
  <title>Registro de Asistencia de Alumnos</title>
</head>
