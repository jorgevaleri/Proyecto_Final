<?php

// DETERMINAR PAGINA ACTUAL
$currentPage = basename($_SERVER['SCRIPT_FILENAME']);

// NORMALIZAMOS EL NOMBRE DEL USUARIO
$personas_nombre   = $_SESSION['personas_nombre']   ?? '';
$personas_apellido = $_SESSION['personas_apellido'] ?? '';
$personas_rol      = $_SESSION['personas_rol']      ?? '';

// NOMBRE A MOSTRAR
$displayName = trim($personas_nombre . ' ' . $personas_apellido);

?>

<header>
  <div class="ancho">
    <!-- LOGO -->
    <div class="logo">
      <a><img src="Imagenes/Logo_3.png" alt="Logo" /></a>
    </div>

    <!-- NAVEGACION ADAPTATIVA SEGUN LA PAGINA -->
    <nav>
      <ul>

        <!-- INDEX -->
        <?php if ($currentPage === 'index.php'): ?>
          <li><a href="logeo.php">Iniciar Sesión</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

          <!-- REGISTRARSE -->
        <?php elseif ($currentPage === 'registrarse.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="logeo.php">Iniciar Sesión</a></li>

          <!-- LOGEO -->
        <?php elseif ($currentPage === 'logeo.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

          <!-- OLVIDE CONTRASEÑA -->
        <?php elseif ($currentPage === 'olvide_contrasenia.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

          <!-- NOMBRE, ROL, CERRAR SESION -->
        <?php elseif (in_array($currentPage, ['menu_principal.php', 'formacion_profesional.php', 'escuelas.php', 'personas.php', 'usuarios.php', 'registros.php', 'perfil.php'])): ?>

          <li>
            <a href="perfil.php">
              <?= $displayName !== '' ? htmlspecialchars($displayName) : 'Perfil' ?><br>
              <small style="font-size:0.9em;color:#666;">
                <?= $personas_rol !== '' ? htmlspecialchars($personas_rol) : '' ?>
              </small>
            </a>
          </li>
          <li><a href="deslogeo.php">Cerrar Sesión</a></li>

          <!-- ENLACE A INICIO -->
        <?php else: ?>
          <li><a href="index.php">Inicio</a></li>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</header>