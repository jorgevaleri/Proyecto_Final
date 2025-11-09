<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// EVITAR QUE EL NAVEGADOR MUESTRE PAGINAS DESDE CACHE
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// COMPROBAR LOGEO
if (empty($_SESSION['user_id'])) {
  header('Location: index.php', true, 303);
  exit;
}

// INICIAR BUFFER
ob_start();

// MOSTRAR ERRORES EN PANTALLA EN DESARROLLO
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// VARIABLES PRINCIPALES
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// ACCIONES
// LISTA / ELIMINADOS
if ($action === 'list' || $action === 'deleted') {
  $showDeleted = $action === 'deleted';
  $sql = "
    SELECT u.usuarios_id, u.usuarios_rol,
           p.personas_apellido, p.personas_nombre,
           u.usuarios_email
    FROM usuarios u
    JOIN personas p ON u.personas_id = p.personas_id
    WHERE u.usuarios_eliminado = " . ($showDeleted ? '1' : '0') . "
    ORDER BY u.usuarios_id
  ";
  $res = mysqli_query($conexion, $sql) or die(mysqli_error($conexion));
}

// AGREGAR
if ($action === 'add') {

  // PERSONAS SIN USUARIO
  $personas = mysqli_query($conexion, "
    SELECT p.personas_id, p.personas_apellido, p.personas_nombre
    FROM personas p
    LEFT JOIN usuarios u ON p.personas_id = u.personas_id AND u.usuarios_eliminado = 0
    WHERE u.usuarios_id IS NULL
    ORDER BY p.personas_apellido, p.personas_nombre
  ");

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $persona_id    = intval($_POST['persona_id'] ?? 0);
    $email         = trim($_POST['usuarios_email'] ?? '');
    $pass          = $_POST['usuarios_clave'] ?? '';
    $pass_conf     = $_POST['usuarios_clave_conf'] ?? '';
    $rol           = $_POST['usuarios_rol'] ?? 'DOCENTE';

    if ($persona_id <= 0) $error = 'Seleccioná una persona.';
    elseif ($email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Ingresá un email válido.';
    elseif ($pass == '' || $pass_conf == '') $error = 'Completá ambos campos de contraseña.';
    elseif ($pass !== $pass_conf) $error = 'Las contraseñas no coinciden.';
    elseif (strlen($pass) < 6) $error = 'La contraseña debe tener al menos 6 caracteres.';

    if (!$error) {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $sql  = "
        INSERT INTO usuarios (personas_id, usuarios_email, usuarios_clave, usuarios_eliminado, usuarios_rol)
        VALUES ($persona_id,'" . mysqli_real_escape_string($conexion, $email) . "','" . mysqli_real_escape_string($conexion, $hash) . "',0,'" . mysqli_real_escape_string($conexion, $rol) . "')
      ";
      if (mysqli_query($conexion, $sql)) {
        header('Location: usuarios.php?saved=1&mode=add');
        exit;
      } else {
        $error = 'Error al guardar: ' . mysqli_error($conexion);
      }
    }
  }
}

// VER MAS
if ($action === 'view') {
  if ($id <= 0) {
    header('Location: usuarios.php');
    exit;
  }

  // CARGAR DATOS
  $stmt = mysqli_prepare($conexion, "
    SELECT u.usuarios_email, u.usuarios_rol,
           p.personas_id, p.personas_apellido, p.personas_nombre,
           p.personas_dni, p.personas_fechnac, p.personas_sexo
    FROM usuarios u
    JOIN personas p ON u.personas_id = p.personas_id
    WHERE u.usuarios_id = ?
    LIMIT 1
  ");
  if (!$stmt) {
    $view_error = 'Error DB';
    $view_row = null;
  } else {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resstmt = mysqli_stmt_get_result($stmt);
    $view_row = $resstmt ? mysqli_fetch_assoc($resstmt) : null;
    mysqli_stmt_close($stmt);
  }

  if ($view_row) {
    $pid = (int)$view_row['personas_id'];
    $doms_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$pid");
    $tels_q = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id=$pid");
    $doms = $doms_q ? mysqli_fetch_all($doms_q, MYSQLI_ASSOC) : [];
    $tels = $tels_q ? mysqli_fetch_all($tels_q, MYSQLI_ASSOC) : [];
    $inst = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM institucional WHERE personas_id={$pid} LIMIT 1"));
  } else {
    $view_row = null;
    $doms = [];
    $tels = [];
    $inst = null;
  }
}

// EDITAR
if ($action === 'edit') {
  if ($id <= 0) header('Location: usuarios.php');
  $sqlData = "
    SELECT p.personas_id, p.personas_apellido, p.personas_nombre, u.usuarios_email, u.usuarios_clave, u.usuarios_rol
    FROM usuarios u
    JOIN personas p ON u.personas_id = p.personas_id
    WHERE u.usuarios_id = $id
  ";
  $resData = mysqli_query($conexion, $sqlData);
  $data    = mysqli_fetch_assoc($resData);
  if (!$data) header('Location: usuarios.php');

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['usuarios_email'] ?? '');
    $pass_actual = $_POST['pass_actual'] ?? '';
    $pass_nueva  = $_POST['pass_nueva'] ?? '';
    $pass_conf   = $_POST['pass_conf'] ?? '';
    $rol         = mysqli_real_escape_string($conexion, $_POST['usuarios_rol'] ?? $data['usuarios_rol']);

    $err = '';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Ingresá un email válido.';
    elseif (($pass_actual !== '' || $pass_nueva !== '' || $pass_conf !== '')) {
      if ($pass_actual === '' || $pass_nueva === '' || $pass_conf === '') $err = 'Completá todos los campos de contraseña para cambiarla.';
      elseif (!password_verify($pass_actual, $data['usuarios_clave'])) $err = 'La contraseña actual no coincide.';
      elseif ($pass_nueva !== $pass_conf) $err = 'La nueva contraseña y su confirmación no coinciden.';
      elseif (strlen($pass_nueva) < 6) $err = 'La nueva contraseña debe tener al menos 6 caracteres.';
    }

    if ($err === '') {
      $sqlUpd = "
        UPDATE usuarios SET
          usuarios_email = '" . mysqli_real_escape_string($conexion, $email) . "',
          usuarios_rol = '$rol'
      ";
      if ($pass_nueva !== '') {
        $hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
        $sqlUpd .= ", usuarios_clave = '" . mysqli_real_escape_string($conexion, $hash) . "'";
      }
      $sqlUpd .= " WHERE usuarios_id = $id";
      if (mysqli_query($conexion, $sqlUpd)) {
        header('Location: usuarios.php?saved=1&mode=edit');
        exit;
      } else $error = 'Error al actualizar: ' . mysqli_error($conexion);
    } else $error = $err;
  }
}

// ELIMINAR
if ($action === 'delete') {
  if ($id <= 0) header('Location: usuarios.php');
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_query($conexion, "UPDATE usuarios SET usuarios_eliminado = 1 WHERE usuarios_id = $id");
    header('Location: usuarios.php?deleted=1');
    exit;
  } else {
    $row = mysqli_fetch_assoc(mysqli_query($conexion, "
      SELECT p.personas_apellido, p.personas_nombre, u.usuarios_email
      FROM usuarios u
      JOIN personas p ON u.personas_id = p.personas_id
      WHERE u.usuarios_id = $id
    "));
  }
}

// RESTAURAR
if ($action === 'restore') {
  if ($id > 0) {
    mysqli_query($conexion, "UPDATE usuarios SET usuarios_eliminado=0 WHERE usuarios_id=$id");
  }
  header('Location: usuarios.php?action=deleted&restored=1');
  exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- HEADER -->
<?php include('header.php'); ?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/estilo_app.css">

<!-- MENU LATERAL -->
<?php include('menu_lateral.php'); ?>

<!-- CUERPO PRINCIPAL -->

<body>
  <main class="fp-page">

    <?php

    // TITULO PRINCIPAL
    if ($action === 'list'):
    ?>
      <h1 class="title">Usuarios</h1>

      <!-- OTROS TITULOS -->
    <?php else:
      $heading = 'Usuarios';
      if ($action === 'add') $heading = 'Agregar Usuario';
      if ($action === 'edit') $heading = 'Editar Usuario';
      if ($action === 'view') $heading = 'Detalle Usuario';
      if ($action === 'delete') $heading = 'Eliminar Usuario';
      if ($action === 'deleted') $heading = 'Usuarios Eliminados';
    ?>
      <h1 class="title"><?= $heading ?></h1>
    <?php endif; ?>

    <!-- ESTILOS PARA AGREGAR Y EDITA -->
    <style>
      .fp-page .three-cols {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-bottom: 12px;
        align-items: start;
      }

      @media (max-width: 900px) {
        .fp-page .three-cols {
          grid-template-columns: 1fr;
        }
      }
    </style>

    <?php if ($action === 'view' && isset($view_row)): ?>

      <!-- VER MAS -->
      <div class="view-panel">

        <!-- DATOS DE USUARIOS -->
        <h3>Datos Usuario</h3>
        <div class="view-grid">
          <div class="view-row">
            <p><strong>Email:</strong> <?= htmlspecialchars($view_row['usuarios_email'] ?? '-') ?></p>
            <p><strong>Rol:</strong> <?= htmlspecialchars($view_row['usuarios_rol'] ?? '-') ?></p>
          </div>
          <div class="view-row">
          </div>
        </div>

        <!-- DATOS PERSONALES -->
        <h3>Datos Personales</h3>
        <div class="view-grid">
          <div class="view-row">
            <p><strong>Apellido:</strong> <?= htmlspecialchars($view_row['personas_apellido'] ?? '-') ?></p>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($view_row['personas_nombre'] ?? '-') ?></p>
            <p><strong>DNI:</strong> <?= htmlspecialchars($view_row['personas_dni'] ?? '-') ?></p>
            <p><strong>Fecha Nac.:</strong> <?= htmlspecialchars($view_row['personas_fechnac'] ?? '-') ?></p>
            <p><strong>Sexo:</strong> <?= htmlspecialchars($view_row['personas_sexo'] ?? '-') ?></p>
          </div>
        </div>

        <!-- INSTITUCIONAL -->
        <h3>Institucional</h3>
        <?php if (!empty($inst)): ?>
          <div class="row">
            <label>Tipo<br><input value="<?= htmlspecialchars($inst['institucional_tipo'] ?? '') ?>" disabled></label>
            <?php if (!empty($inst['escuelas_id'])):
              $esc = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT escuelas_nombre FROM escuelas WHERE escuelas_id=" . (int)$inst['escuelas_id']));
            ?>
              <label>Escuela<br><input value="<?= htmlspecialchars($esc['escuelas_nombre'] ?? '') ?>" disabled></label>
            <?php endif; ?>
            <?php if (!empty($inst['formaciones_profesionales_id'])):
              $fo = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_id=" . (int)$inst['formaciones_profesionales_id']));
            ?>
              <label>Formación<br><input value="<?= htmlspecialchars($fo['formaciones_profesionales_nombre'] ?? '') ?>" disabled></label>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <p>No hay datos institucionales.</p>
        <?php endif; ?>

        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <div class="blocks">
          <?php if (!empty($doms) && is_array($doms)): foreach ($doms as $d): ?>
              <div class="dom-block">
                <label>Calle y número<br><input value="<?= htmlspecialchars($d['domicilios_calle'] ?? '') ?>" disabled></label>
                <label>Descripción<br><input value="<?= htmlspecialchars($d['domicilios_descripcion'] ?? '') ?>" disabled></label>
                <label>Predeterminado<br><input value="<?= (!empty($d['domicilios_predeterminado']) ? 'Sí' : 'No') ?>" disabled></label>
                <?php if (!empty($d['domicilios_latitud']) && !empty($d['domicilios_longitud'])): ?>
                  <div class="map" style="height:150px;">
                    <iframe width="100%" height="150" frameborder="0" style="border:0"
                      src="https://maps.google.com/maps?q=<?= rawurlencode($d['domicilios_latitud']) ?>,<?= rawurlencode($d['domicilios_longitud']) ?>&z=15&output=embed"
                      loading="lazy"></iframe>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach;
          else: ?>
            <p>No hay domicilios registrados.</p>
          <?php endif; ?>
        </div>

        <!-- TELEFONOS -->
        <h3>Teléfonos</h3>
        <div class="blocks">
          <?php if (!empty($tels) && is_array($tels)): foreach ($tels as $t): ?>
              <div class="tel-block">
                <label>Número<br><input value="<?= htmlspecialchars($t['telefonos_numero'] ?? '') ?>" disabled></label>
                <label>Descripción<br><input value="<?= htmlspecialchars($t['telefonos_descripcion'] ?? '') ?>" disabled></label>
                <label>Predeterminado<br><input value="<?= (!empty($t['telefonos_predeterminado']) ? 'Sí' : 'No') ?>" disabled></label>
              </div>
            <?php endforeach;
          else: ?>
            <p>No hay teléfonos registrados.</p>
          <?php endif; ?>
        </div>

        <p style="margin-top:12px;">
          <a href="usuarios.php" class="pill">Volver al listado</a>
        </p>
      </div>

      <!-- AGREGAR -->
    <?php elseif ($action === 'add'): ?>

      <!-- CONTENEDOR DE MENSAJE DE ERROR -->
      <div id="usuariosFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
        <?php if (!empty($error)): ?>
          <p class="msg"><?= htmlspecialchars($error) ?></p>
        <?php endif ?>
      </div>

      <!-- FORMULARIO -->
      <form id="form-add-user" method="post" class="botones" novalidate>
        <div class="three-cols">
          <label>
            Persona
            <select name="persona_id">
              <option value="">-- Seleccione --</option>
              <?php if (isset($personas)) while ($p = mysqli_fetch_assoc($personas)): ?>
                <option value="<?= $p['personas_id'] ?>" <?= (isset($_POST['persona_id']) && $_POST['persona_id'] == $p['personas_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['personas_apellido'] . ', ' . $p['personas_nombre']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </label>

          <label>
            Rol
            <select name="usuarios_rol">
              <option value="ADMINISTRADOR">ADMINISTRADOR</option>
              <option value="DIRECTOR">DIRECTOR</option>
              <option value="DOCENTE" selected>DOCENTE</option>
            </select>
          </label>

          <label>
            Email
            <input type="email" name="usuarios_email" value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
          </label>
        </div>

        <div class="three-cols">
          <label>
            Contraseña
            <input type="password" name="usuarios_clave" id="usuarios_clave" placeholder="********">
            <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave" title="Mostrar/Ocultar"></i>
          </label>

          <label>
            Confirmar Contraseña
            <input type="password" name="usuarios_clave_conf" id="usuarios_clave_conf" placeholder="********">
            <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave_conf" title="Mostrar/Ocultar"></i>
          </label>
          <div></div>
        </div>

        <!-- BOTONES -->
        <div class="three-cols">
          <div style="grid-column: 1 / -1; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <button type="submit" class="pill primary"><i class="bi bi-save"></i> Guardar</button>
            <a href="usuarios.php" class="pill">Cancelar</a>
          </div>
        </div>
      </form>

      <!-- EDITAR -->
    <?php elseif ($action === 'edit'): ?>

        <!-- CONTENEDOR DE MENSAJE DE ERROR -->
      <div id="usuariosFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
        <?php if (!empty($error)): ?>
          <p class="msg"><?= htmlspecialchars($error) ?></p>
        <?php endif ?>
      </div>

      <!-- FORMULARIO -->
      <form id="form-edit-user" method="post" class="botones" novalidate>
        <div class="three-cols">
          <label>
            Persona
            <input type="text" value="<?= htmlspecialchars($data['personas_apellido'] . ', ' . $data['personas_nombre']) ?>" disabled>
          </label>

          <label>
            Rol
            <select name="usuarios_rol">
              <option value="ADMINISTRADOR" <?= ($data['usuarios_rol'] == 'ADMINISTRADOR') ? 'selected' : '' ?>>ADMINISTRADOR</option>
              <option value="DIRECTOR" <?= ($data['usuarios_rol'] == 'DIRECTOR') ? 'selected' : '' ?>>DIRECTOR</option>
              <option value="DOCENTE" <?= ($data['usuarios_rol'] == 'DOCENTE') ? 'selected' : '' ?>>DOCENTE</option>
            </select>
          </label>

          <label>
            Email
            <input type="email" name="usuarios_email" value="<?= htmlspecialchars($_POST['usuarios_email'] ?? $data['usuarios_email']) ?>">
          </label>
        </div>

        <div class="three-cols">
          <label>
            Contraseña Actual<small> (dejar vacío si no cambia)</small>
            <input type="password" name="pass_actual" id="pass_actual" placeholder="Contraseña actual">
            <i class="bi bi-eye-slash toggle-password" toggle="#pass_actual" title="Mostrar/Ocultar"></i>
          </label>

          <label>
            Nueva Contraseña
            <input type="password" name="pass_nueva" id="pass_nueva" placeholder="Nueva contraseña">
            <i class="bi bi-eye-slash toggle-password" toggle="#pass_nueva" title="Mostrar/Ocultar"></i>
          </label>

          <label>
            Repetir Contraseña
            <input type="password" name="pass_conf" id="pass_conf" placeholder="Confirmar contraseña">
            <i class="bi bi-eye-slash toggle-password" toggle="#pass_conf" title="Mostrar/Ocultar"></i>
          </label>
        </div>

        <!-- BOTONES -->
        <div class="three-cols">
          <div style="grid-column: 1 / -1; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <button type="submit" class="pill primary"><i class="bi bi-save"></i> Guardar cambios</button>
            <a href="usuarios.php" class="pill">Cancelar</a>
          </div>
        </div>
      </form>

      <!-- ELIMINAR -->
    <?php elseif ($action === 'delete'): ?>
      <div class="delete-panel">
        <h2>Confirmar eliminación</h2>
        <p>¿Seguro que querés eliminar al usuario <strong><?= htmlspecialchars($row['personas_apellido'] . ', ' . $row['personas_nombre'] ?? '') ?></strong> (<?= htmlspecialchars($row['usuarios_email'] ?? '') ?>)?</p>
        <form id="form-delete-user" method="post" class="botones" novalidate>
          <div id="usuariosFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
            <?php if (!empty($error)): ?>
              <p class="msg"><?= htmlspecialchars($error) ?></p>
            <?php endif ?>
          </div>

          <!-- BOTONES -->
          <li class="boton-agregar">
            <button type="submit" class="pill primary">Sí, eliminar</button>
          </li>
          <li class="boton-volver">
            <a href="usuarios.php" class="pill">No, cancelar</a>
          </li>
        </form>
      </div>

      <!-- LISTA / ELIMINADOS -->
    <?php else: ?>
      <ul class="botones">
        <?php if (!$showDeleted): ?>
          <li class="boton-agregar"><a href="usuarios.php?action=add" class="pill"><i class="bi bi-person-plus"></i> Agregar Usuario</a></li>
        <?php endif; ?>
        <li class="boton-volver">
          <?php if ($action === 'deleted'): ?>
            <a href="usuarios.php" class="pill"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a>
          <?php else: ?>
            <a href="usuarios.php?action=deleted" class="pill"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a>
          <?php endif; ?>
        </li>
      </ul>

      <div class="contenedor">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Apellido</th>
              <th>Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php $cnt = 0;
            if (!empty($res)) while ($r = mysqli_fetch_assoc($res)): ?>
              <tr>
                <td><?= ++$cnt ?></td>
                <td class="text-left"><?= htmlspecialchars($r['personas_apellido']) ?></td>
                <td class="text-left"><?= htmlspecialchars($r['personas_nombre']) ?></td>
                <td><?= htmlspecialchars($r['usuarios_email']) ?></td>
                <td><?= htmlspecialchars($r['usuarios_rol']) ?></td>
                <td>
                  <?php if ($action === 'list'): ?>
                    <a href="usuarios.php?action=view&id=<?= $r['usuarios_id'] ?>" title="Ver más" class="accion"><i class="bi bi-eye"></i></a>
                    <a href="usuarios.php?action=edit&id=<?= $r['usuarios_id'] ?>" title="Editar" class="accion"><i class="bi bi-pencil"></i></a>
                    <a href="usuarios.php?action=delete&id=<?= $r['usuarios_id'] ?>" title="Eliminar" class="accion"><i class="bi bi-trash3"></i></a>
                  <?php else: ?>
                    <a href="usuarios.php?action=restore&id=<?= $r['usuarios_id'] ?>" title="Restaurar" class="accion"><i class="bi bi-arrow-counterclockwise"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </main>

  <!-- FOOTER -->
  <?php include('footer.php'); ?>

  <!-- SWEETALERT2 -->
  <?php
  $saved = isset($_GET['saved']) ? (int)$_GET['saved'] : 0;
  $saved_mode = $_GET['mode'] ?? '';
  $deleted = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;
  $restored = isset($_GET['restored']) ? (int)$_GET['restored'] : 0;
  ?>

  <?php if ($saved || $deleted || $restored): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($saved):
          $msg = ($saved_mode === 'add') ? 'Usuario agregado correctamente.' : 'Cambios guardados correctamente.';
        ?>
          Swal.fire({
            icon: 'success',
            title: 'Guardado',
            text: <?= json_encode($msg) ?>,
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'usuarios.php';
          });
        <?php elseif ($deleted): ?>
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: 'El usuario fue eliminado correctamente.',
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'usuarios.php';
          });
        <?php elseif ($restored): ?>
          Swal.fire({
            icon: 'success',
            title: 'Restaurado',
            text: 'El usuario fue restaurado correctamente.',
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'usuarios.php?action=deleted';
          });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- SCRIPTS -->
  <script src="JS/usuarios.js" defer></script>

</body>

</html>

<!-- FINALIZAR BUFFER -->
<?php ob_end_flush(); ?>