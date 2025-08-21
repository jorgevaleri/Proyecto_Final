<?php
session_start();

// Evitar que el navegador muestre páginas desde cache después del logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Comprobar login: adaptá 'user_id' por la variable de sesión que uses
if (empty($_SESSION['user_id'])) {
  header('Location: index.php', true, 303);
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- HEADER -->
<?php include('header.php'); ?>

<!-- ESTILOS CSS -->
 <link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_app.css">

<!-- MENU LATERAR -->
<?php include('menu_lateral.php'); ?>

<body class="body-menu-principal">
    <main class="cuerpo-menu-principal">
        <div class="logo_grande">
            <center><img src="Imagenes/Logo_4.jpg" alt="centered image" width="450" height="450"></center>
        </div>
    </main>
</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>