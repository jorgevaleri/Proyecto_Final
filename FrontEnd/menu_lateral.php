<!-- HEAD -->
<?php include('head.php');
$role = current_role();
?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/estilo_app.css">

<!-- MENU LATERAL -->
<aside id="sidebar" class="cuerpo-menu-vertical" role="navigation" aria-label="Menú principal">
  <div class="menu-interno">
    <ul>

      <!-- BOTONES / LINK (ADMINISTRADOR) -->
      <?php if (is_admin()): ?>
        <li>
          <button class="menu-btn" aria-label="Escuelas" onclick="window.location.href='escuelas.php'">
            <i class="bi bi-building" aria-hidden="true"></i>
            <span class="label">Escuelas</span>
          </button>
        </li>

        <li>
          <button class="menu-btn" aria-label="Formaciones Profesionales" onclick="window.location.href='formacion_profesional.php'">
            <i class="bi bi-book" aria-hidden="true"></i>
            <span class="label">Formaciones Profesionales</span>
          </button>
        </li>

        <li>
          <button class="menu-btn" aria-label="Personas" onclick="window.location.href='personas.php'">
            <i class="bi bi-people-fill" aria-hidden="true"></i>
            <span class="label">Personas</span>
          </button>
        </li>

        <li>
          <button class="menu-btn" aria-label="Usuarios" onclick="window.location.href='usuarios.php'">
            <i class="bi bi-person-badge" aria-hidden="true"></i>
            <span class="label">Usuarios</span>
          </button>
        </li>

        <li>
          <button class="menu-btn" aria-label="Registros" onclick="window.location.href='registros.php'">
            <i class="bi bi-journal-text" aria-hidden="true"></i>
            <span class="label">Registros</span>
          </button>
        </li>
      <?php else: ?>

        <!-- BOTONES / LINK (DIRECTOR Y DOCENTE) -->
        <?php if (is_director() || is_docente()): ?>
          <li>
            <button class="menu-btn" aria-label="Personas" onclick="window.location.href='personas.php?tipo=director'">
              <i class="bi bi-people-fill" aria-hidden="true"></i>
              <span class="label">Personas</span>
            </button>
          </li>

          <li>
            <button class="menu-btn" aria-label="Registros" onclick="window.location.href='registros.php'">
              <i class="bi bi-journal-text" aria-hidden="true"></i>
              <span class="label">Registros</span>
            </button>
          </li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
</aside>