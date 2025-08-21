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

<?php
ob_start();
include('head.php');         // Carga style.css y Bootstrap Icons
include('header.php');
include('menu_lateral.php');
include('../BackEnd/conexion.php');

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$action = $_GET['action'] ?? 'list';
$id     = intval($_GET['id'] ?? 0);
$error  = '';
?>

<main class="fp-page">
  <?php switch ($action):

      // ────────────────────────────────────────────
      // LISTAR USUARIOS (activos o eliminados)
    case 'list':
    case 'deleted':
      $showDeleted = $action === 'deleted';
      // Consulta general para listado
      $sql = "
        SELECT u.usuarios_id,
        u.usuarios_rol,
               p.personas_apellido, p.personas_nombre,
               u.usuarios_email
        FROM usuarios u
        JOIN personas p ON u.personas_id = p.personas_id
        WHERE u.usuarios_eliminado = " . ($showDeleted ? '1' : '0') . "
        ORDER BY u.usuarios_id
      ";
      $res = mysqli_query($conexion, $sql);
  ?>
      <h1 class="title">
        <?= $showDeleted ? 'Usuarios Eliminados' : 'Listado de Usuarios' ?>
      </h1>
      <ul class="botones">
        <li class="boton-agregar">
          <a href="usuarios.php?action=add">
            <i class="bi bi-person-plus"></i> Agregar Usuario
          </a>
        </li>
        <li class="boton-volver">
          <?php if ($showDeleted): ?>
            <a href="usuarios.php"><i class="bi bi-eye"></i> Ocultar Eliminados</a>
          <?php else: ?>
            <a href="usuarios.php?action=deleted"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a>
          <?php endif ?>
        </li>
      </ul>

      <div class="contenedor">
        <table class="tabla1">
          <thead>
            <tr>
              <th>#</th>
              <th>Apellido, Nombre</th>
              <th>Email</th>
              <th>Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php $cnt = 0;
            while ($row = mysqli_fetch_assoc($res)): $cnt++ ?>
              <tr>
                <td><?= $cnt ?></td>
                <td><?= htmlspecialchars($row['personas_apellido'] . ', ' . $row['personas_nombre']) ?></td>
                <td><?= htmlspecialchars($row['usuarios_email']) ?></td>
                <td><?= htmlspecialchars($row['usuarios_rol']) ?></td>
                <td>
                  <?php if ($showDeleted): ?>
                    <a href="usuarios.php?action=restore&id=<?= $row['usuarios_id'] ?>" title="Restaurar">
                      <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                  <?php else: ?>
                    <a href="usuarios.php?action=view&id=<?= $row['usuarios_id'] ?>" title="Ver más"><i class="bi bi-eye"></i></a>
                    <a href="usuarios.php?action=edit&id=<?= $row['usuarios_id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a href="usuarios.php?action=delete&id=<?= $row['usuarios_id'] ?>" title="Eliminar"><i class="bi bi-trash3" style="color:red;"></i></a>
                  <?php endif ?>
                </td>
              </tr>
            <?php endwhile ?>
          </tbody>
        </table>
      </div>
    <?php
      break;

    // ────────────────────────────────────────────
    // AGREGAR USUARIO
    case 'add':
      $error = '';
      // Personas sin usuario activo
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
        $rol           = $_POST['usuarios_rol'] ?? 'DOCENTE'; // valor por defecto

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
            header('Location: usuarios.php');
            exit;
          } else {
            $error = 'Error al guardar: ' . mysqli_error($conexion);
          }
        }
      }
    ?>
      <h1 class="title">Agregar Usuario</h1>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif ?>
      <form method="post" class="botones">
        <li class="boton-agregar">
          <label>Persona</label>
          <select name="persona_id" required>
            <option value="">-- Seleccione --</option>
            <?php while ($p = mysqli_fetch_assoc($personas)): ?>
              <option value="<?= $p['personas_id'] ?>" <?= (isset($_POST['persona_id']) && $_POST['persona_id'] == $p['personas_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['personas_apellido'] . ', ' . $p['personas_nombre']) ?>
              </option>
            <?php endwhile ?>
          </select>
        </li>
        <li class="boton-agregar">
          <label>Rol</label>
          <select name="usuarios_rol" required>
            <option value="ADMINISTRADOR">ADMINISTRADOR</option>
            <option value="DIRECTOR">DIRECTOR</option>
            <option value="DOCENTE" selected>DOCENTE</option>
          </select>
          <label>Email</label>
          <input type="email" name="usuarios_email" required value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
        </li>
        <li class="boton-agregar password-field">
          <label>Contraseña</label>
          <input type="password" name="usuarios_clave" id="usuarios_clave" placeholder="********" required>
          <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave" title="Mostrar/Ocultar"></i>
        </li>
        <li class="boton-agregar password-field">
          <label>Confirmar Contraseña</label>
          <input type="password" name="usuarios_clave_conf" id="usuarios_clave_conf" placeholder="********" required>
          <i class="bi bi-eye-slash toggle-password" toggle="#usuarios_clave_conf" title="Mostrar/Ocultar"></i>
        </li>
        <li class="boton-volver">
          <button type="submit"><i class="bi bi-save"></i> Guardar</button>
        </li>
        <li class="boton-volver">
          <a href="usuarios.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
        </li>
      </form>
      <script>
        document.querySelectorAll('.toggle-password').forEach(btn => {
          btn.addEventListener('click', () => {
            const inp = document.querySelector(btn.getAttribute('toggle'));
            if (inp.type === 'password') {
              inp.type = 'text';
              btn.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
              inp.type = 'password';
              btn.classList.replace('bi-eye', 'bi-eye-slash');
            }
          });
        });
      </script>
    <?php
      break;

    // ────────────────────────────────────────────
    // VER MÁS (detalle con iframes como en personas.php)
    // VER MÁS (detalle)
case 'view':
  if ($id <= 0) { header('Location: usuarios.php'); exit; }

  try {
    // Prepared statement: traemos usuario + persona y el institucional_tipo (si existe)
    $stmt = mysqli_prepare($conexion, "
      SELECT u.usuarios_email, u.usuarios_rol,
             p.personas_id, p.personas_apellido, p.personas_nombre,
             p.personas_dni, p.personas_fechnac, p.personas_sexo,
             (SELECT i.institucional_tipo
                FROM institucional i
               WHERE i.personas_id = p.personas_id
               LIMIT 1) AS institucional_tipo
      FROM usuarios u
      JOIN personas p ON u.personas_id = p.personas_id
      WHERE u.usuarios_id = ?
      LIMIT 1
    ");
    if (!$stmt) throw new Exception('Error prepare: ' . mysqli_error($conexion));

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $r = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$r) {
      echo "<h1 class=\"title\">Usuario no encontrado</h1>";
      echo "<p>El usuario con ID " . htmlspecialchars($id) . " no existe.</p>";
      echo '<p><a href="usuarios.php">Volver al listado</a></p>';
      break;
    }

    // Domicilios y teléfonos
    $pid = (int)$r['personas_id'];
    $doms = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id={$pid}");
    $tels = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id={$pid}");

    ?>
    <!-- ESTILOS -->
     <link rel="stylesheet" href="CSS/style_common.css">
     <link rel="stylesheet" href="CSS/style_app.css">
     
    <h1 class="title">Detalle de Usuario</h1>

    <!-- Datos Usuario -->
    <h2>Datos Usuario</h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($r['usuarios_email']) ?></p>
    <p><strong>Rol:</strong> <?= htmlspecialchars($r['usuarios_rol'] ?? '-') ?></p>

    <!-- Datos Personales -->
    <h2>Datos Personales</h2>
    <p><strong>Apellido:</strong> <?= htmlspecialchars($r['personas_apellido']) ?></p>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($r['personas_nombre']) ?></p>
    <p><strong>DNI:</strong> <?= htmlspecialchars($r['personas_dni']) ?></p>
    <p><strong>Fecha Nac.:</strong> <?= htmlspecialchars($r['personas_fechnac']) ?></p>
    <p><strong>Sexo:</strong> <?= htmlspecialchars($r['personas_sexo']) ?></p>

    <!-- Institucional / Tipo -->
    <h2>Institucional</h2>
    <p><strong>Tipo institucional:</strong>
      <?= htmlspecialchars($r['institucional_tipo'] ?? 'No registrado') ?>
    </p>

    <!-- Domicilios -->
    <h2>Domicilios</h2>
    <?php if ($doms && mysqli_num_rows($doms) === 0): ?>
      <p>No se encontraron domicilios.</p>
    <?php endif; ?>
    <?php while ($d = $doms ? mysqli_fetch_assoc($doms) : null): if (!$d) break; ?>
      <div class="dom-block">
        <p><strong>Calle:</strong> <?= htmlspecialchars($d['domicilios_calle']) ?></p>
        <p><strong>Desc.:</strong> <?= htmlspecialchars($d['domicilios_descripcion']) ?></p>
        <p><strong>Predeterminado:</strong> <?= $d['domicilios_predeterminado'] ? 'Sí' : 'No' ?></p>
        <?php if (!empty($d['domicilios_latitud']) && !empty($d['domicilios_longitud'])): ?>
          <iframe width="100%" height="200" frameborder="0" style="border:0"
                  src="https://maps.google.com/maps?q=<?= $d['domicilios_latitud'] ?>,<?= $d['domicilios_longitud'] ?>&z=15&output=embed"
                  loading="lazy"></iframe>
        <?php endif ?>
      </div>
    <?php endwhile ?>

    <!-- Teléfonos -->
    <h2>Teléfonos</h2>
    <?php if ($tels && mysqli_num_rows($tels) === 0): ?>
      <p>No se encontraron teléfonos.</p>
    <?php endif; ?>
    <?php while ($t = $tels ? mysqli_fetch_assoc($tels) : null): if (!$t) break; ?>
      <div class="tel-block">
        <p><strong>Teléfono:</strong> <?= htmlspecialchars($t['telefonos_numero']) ?></p>
        <p><strong>Desc.:</strong> <?= htmlspecialchars($t['telefonos_descripcion']) ?></p>
        <p><strong>Predeterminado:</strong> <?= $t['telefonos_predeterminado'] ? 'Sí' : 'No' ?></p>
      </div>
    <?php endwhile ?>

    <ul class="botones">
      <li class="boton-volver">
        <a href="usuarios.php"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a>
      </li>
    </ul>
    <?php

  } catch (Exception $e) {
    http_response_code(500);
    echo "<h1>Error interno</h1>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("usuarios.php view error: " . $e->getMessage());
    break;
  }

  break;


    // ────────────────────────────────────────────
    // EDITAR USUARIO
    case 'edit':
      if ($id <= 0) header('Location: usuarios.php');
      $error = '';

      // 1) Traigo datos actuales (apellido, nombre, email y hash de clave)
      $sqlData = "
      SELECT p.personas_apellido,
             p.personas_nombre,
             u.usuarios_email,
             u.usuarios_clave,
             u.usuarios_rol
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
        $pass_nueva  = $_POST['pass_nueva']  ?? '';
        $pass_conf   = $_POST['pass_conf']   ?? '';

        // Validar email
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error = 'Ingresá un email válido.';
        }
        // Si se completó algún campo de clave, validamos el bloque completo
        elseif ($pass_actual !== '' || $pass_nueva !== '' || $pass_conf !== '') {
          if ($pass_actual === '' || $pass_nueva === '' || $pass_conf === '') {
            $error = 'Completá todos los campos de contraseña para cambiarla.';
          } elseif (!password_verify($pass_actual, $data['usuarios_clave'])) {
            $error = 'La contraseña actual no coincide.';
          } elseif ($pass_nueva !== $pass_conf) {
            $error = 'La nueva contraseña y su confirmación no coinciden.';
          } elseif (strlen($pass_nueva) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
          }
        }

        // Si no hubo errores, actualizamos
        if (!$error) {
          $rol = mysqli_real_escape_string($conexion, $_POST['usuarios_rol'] ?? 'DOCENTE');

          $sqlUpd = "
    UPDATE usuarios SET
      usuarios_email = '" . mysqli_real_escape_string($conexion, $email) . "',
      usuarios_rol = '$rol'
       ";
          // Solo tocar clave si el usuario completó el bloque de cambio
          if ($pass_nueva !== '') {
            $hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $sqlUpd .= ",
            usuarios_clave = '" . mysqli_real_escape_string($conexion, $hash) . "'";
          }
          $sqlUpd .= "
          WHERE usuarios_id = $id
        ";
          if (mysqli_query($conexion, $sqlUpd)) {
            header('Location: usuarios.php');
            exit;
          } else {
            $error = 'Error al actualizar: ' . mysqli_error($conexion);
          }
        }
      }
    ?>
      <h1 class="title">Editar Usuario</h1>
      <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
      <?php endif ?>

      <form method="post" class="botones">
        <!-- Apellido y Nombre (solo lectura) -->
        <li class="boton-agregar">
          <label>Persona</label>
          <p>
            <?= htmlspecialchars($data['personas_apellido'] . ', ' . $data['personas_nombre']) ?>
          </p>
        </li>

        <!-- Email editable -->
        <li class="boton-agregar">
          <label>Email</label>
          <input
            type="email"
            name="usuarios_email"
            required
            placeholder="Email"
            value="<?= htmlspecialchars($_POST['usuarios_email'] ?? $data['usuarios_email']) ?>">
        </li>

        <!-- rol editable -->
        <li class="boton-agregar">
          <label>Rol</label>
          <select name="usuarios_rol" required>
            <option value="ADMINISTRADOR" <?= ($data['usuarios_rol'] == 'ADMINISTRADOR') ? 'selected' : '' ?>>ADMINISTRADOR</option>
            <option value="DIRECTOR" <?= ($data['usuarios_rol'] == 'DIRECTOR') ? 'selected' : '' ?>>DIRECTOR</option>
            <option value="DOCENTE" <?= ($data['usuarios_rol'] == 'DOCENTE') ? 'selected' : '' ?>>DOCENTE</option>
          </select>
        </li>

        <!-- CONTRASEÑA: bloque opcional -->
        <li class="boton-agregar password-field">
          <label>Contraseña Actual</label>
          <input
            type="password"
            name="pass_actual"
            id="pass_actual"
            placeholder="Contraseña Actual">
          <i
            class="bi bi-eye-slash toggle-password"
            toggle="#pass_actual"
            title="Mostrar/Ocultar contraseña"></i>
        </li>
        <li class="boton-agregar password-field">
          <label>Nueva Contraseña</label>
          <input
            type="password"
            name="pass_nueva"
            id="pass_nueva"
            placeholder="Nueva contraseña">
          <i
            class="bi bi-eye-slash toggle-password"
            toggle="#pass_nueva"
            title="Mostrar/Ocultar contraseña"></i>
        </li>
        <li class="boton-agregar password-field">
          <label>Confirmar Nueva</label>
          <input
            type="password"
            name="pass_conf"
            id="pass_conf"
            placeholder="Confirmar contraseña">
          <i
            class="bi bi-eye-slash toggle-password"
            toggle="#pass_conf"
            title="Mostrar/Ocultar contraseña"></i>
        </li>

        <!-- Botones de acción -->
        <li class="boton-volver">
          <button type="submit"><i class="bi bi-save"></i> Guardar Cambios</button>
        </li>
        <li class="boton-volver">
          <a href="usuarios.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
        </li>
      </form>

      <!-- Script para alternar visibilidad de contraseñas -->
      <script>
        document.querySelectorAll('.toggle-password').forEach(btn => {
          btn.addEventListener('click', () => {
            const input = document.querySelector(btn.getAttribute('toggle'));
            if (input.type === 'password') {
              input.type = 'text';
              btn.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
              input.type = 'password';
              btn.classList.replace('bi-eye', 'bi-eye-slash');
            }
          });
        });
      </script>
    <?php
      break;

    // ────────────────────────────────────────────
    // ELIMINAR LÓGICO
    case 'delete':
      if ($id <= 0) header('Location: usuarios.php');
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        mysqli_query($conexion, "
        UPDATE usuarios
        SET usuarios_eliminado = 1
        WHERE usuarios_id = $id
      ");
        header('Location: usuarios.php');
        exit;
      }
      // Cargo datos para confirmar
      $row = mysqli_fetch_assoc(mysqli_query($conexion, "
      SELECT p.personas_apellido, p.personas_nombre, u.usuarios_email
      FROM usuarios u
      JOIN personas p ON u.personas_id = p.personas_id
      WHERE u.usuarios_id = $id
    "));
    ?>
      <h1 class="title">Eliminar Usuario</h1>
      <p>¿Seguro que querés eliminar al usuario <strong>
          <?= htmlspecialchars($row['personas_apellido'] . ', ' . $row['personas_nombre']) ?>
          (<?= htmlspecialchars($row['usuarios_email']) ?>)
        </strong>?</p>
      <form method="post" class="botones">
        <li class="boton-agregar">
          <button type="submit"><i class="bi bi-trash3"></i> Sí, eliminar</button>
        </li>
        <li class="boton-volver">
          <a href="usuarios.php"><i class="bi bi-arrow-left-circle"></i> No, cancelar</a>
        </li>
      </form>
  <?php
      break;

    // ────────────────────────────────────────────
    // RESTAURAR
    case 'restore':
      if ($id > 0) {
        mysqli_query($conexion, "UPDATE usuarios SET usuarios_eliminado=0 WHERE usuarios_id=$id");
      }
      header('Location: usuarios.php?action=deleted');
      exit;
      break;

  endswitch; ?>

</main>

<?php
include('footer.php');
ob_end_flush();
?>