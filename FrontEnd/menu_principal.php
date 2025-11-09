<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// EVITAR QUE EL NAVEGADOR MUESTRE PAGINAS DESDE CACHE
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
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/estilo_app.css">

<!-- MENU LATERAR -->
<?php include('menu_lateral.php'); ?>

<!-- CUERPO PRINCIPAL -->
<body class="body-menu-principal">
    <main class="cuerpo-menu-principal">

    <!-- LOGO -->
        <div class="logo_grande">
            <img src="Imagenes/Logo_4.jpg" alt="Logo grande" class="logo-img" width="450" height="450">
        </div>
    </main>
</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>