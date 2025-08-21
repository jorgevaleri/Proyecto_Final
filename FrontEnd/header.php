<?php
// header.php

// Si no hay sesión iniciada, la iniciamos (es mejor iniciarla en head.php si lo prefieres)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Determina la página activa
$currentPage = basename($_SERVER['SCRIPT_FILENAME']);

// Normalizamos el nombre del usuario (evita warnings si la clave no existe)
$personas_nombre   = $_SESSION['personas_nombre']   ?? '';
$personas_apellido = $_SESSION['personas_apellido'] ?? '';
$personas_rol      = $_SESSION['personas_rol']      ?? '';

// Nombre a mostrar (vacío si no hay usuario)
$displayName = trim($personas_nombre . ' ' . $personas_apellido);
?>
<header>
  <div class="ancho">
    <div class="logo">
      <a href="index.php">
        <img src="Imagenes/Logo_3.png" alt="Logo" />
      </a>
    </div>
    <nav>
      <ul>
        <?php if ($currentPage === 'index.php'): ?>
          <li><a href="logeo.php">Iniciar Sesión</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

        <?php elseif ($currentPage === 'registrarse.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="logeo.php">Iniciar Sesión</a></li>

        <?php elseif ($currentPage === 'logeo.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

          <?php elseif ($currentPage === 'olvide-contraseña.php'): ?>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="registrarse.php">Registrarse</a></li>

        <?php elseif (in_array($currentPage, ['menu_principal.php', 'formacion_profesional.php', 'escuelas.php', 'personas.php', 'usuarios.php', 'registros.php', 'perfil.php'])): ?>
          <!-- Si el usuario no está logueado, mostramos 'Perfil' sin nombre -->
          <li>
            <a href="perfil.php">
              <?= $displayName !== '' ? htmlspecialchars($displayName) : 'Perfil' ?><br>
              <small style="font-size:0.9em;color:#666;">
                <?= $personas_rol !== '' ? htmlspecialchars($personas_rol) : '' ?>
              </small>
            </a>
          </li>
          <li><a href="deslogeo.php">Cerrar Sesión</a></li>

        <?php else: ?>
          <li><a href="index.php">Inicio</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>