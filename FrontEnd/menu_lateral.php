<!-- HEAD -->
<?php include('head.php');
$role = current_role();
?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_app.css">

<!-- menu_lateral.php -->
<aside class="cuerpo-menu-vertical">
  <div class="menu-interno">
    <ul>
      <?php if (is_admin()): ?>
        <!-- Si admin, dejamos todo como estaba -->
        <li><button onclick="window.location.href='escuelas.php'">
            <i class="bi bi-building"></i> Escuelas
          </button>
        </li>

        <li>
          <button onclick="window.location.href='formacion_profesional.php'">
            <i class="bi bi-book"></i> Formaciones Profesionales
          </button>
        </li>

        <li>
          <button onclick="window.location.href='personas.php?tipo=director'">
            <i class="bi bi-people-fill"></i> Personas
          </button>
        </li>

        <li>
          <button onclick="window.location.href='usuarios.php'">
            <i class="bi bi-person-badge"></i> Usuarios
          </button>
        </li>

        <li>
          <button onclick="window.location.href='registros.php'">
            <i class="bi bi-journal-text"></i> Registros
          </button>
        </li>
      <?php else: ?>

        <!-- Para DIRECTOR y DOCENTE sólo mostramos Personas y Registros (si corresponde) -->
        <?php if (is_director() || is_docente()): ?>
          <li>
            <button onclick="window.location.href='personas.php?tipo=director'">
            <i class="bi bi-people-fill"></i> Personas
          </button>
          </li>

          <li>
          <button onclick="window.location.href='registros.php'">
            <i class="bi bi-journal-text"></i> Registros
          </button>
        </li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </div>
</aside>