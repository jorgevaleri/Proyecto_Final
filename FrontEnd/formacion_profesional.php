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
  // Activamos buffer para poder usar header() tras includes
  ob_start();

  include('head.php');
  include('header.php');
  include('menu_lateral.php');
  include('../BackEnd/conexion.php');

  // recoge acción (por defecto: list)
  $action = $_GET['action'] ?? 'list';
  $id     = $_GET['id']     ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- ESTILOS -->
   <link rel="stylesheet" href="CSS/style_common.css">
  <link rel="stylesheet" href="CSS/style_app.css">
</head>
<body>
  <main class="fp-page">
    <?php switch($action):

      // ────────────────────────────────────────────
      case 'add':
      // ────────────────────────────────────────────
        if($_SERVER['REQUEST_METHOD']==='POST'){
          $nombre = trim($_POST['formaciones_profesionales_nombre']);
          if($nombre !== ''){
            mysqli_query($conexion,
              "INSERT INTO formaciones_profesionales(formaciones_profesionales_nombre)
               VALUES ('".mysqli_real_escape_string($conexion, $nombre)."')"
            );
            header("Location: formacion_profesional.php");
            exit;
          } else {
            $error = 'Debe ingresar un nombre.';
          }
        }
    ?>
      <h1 class="title">Agregar Formación Profesional</h1>
      <?php if(!empty($error)): ?>
        <p class="error"><?= $error ?></p>
      <?php endif ?>

      <form method="post" class="botones">
        <li class="boton-agregar">
          <input
            type="text"
            name="formaciones_profesionales_nombre"
            placeholder="Nombre de la formación"
            style="width:auto;"
          >
        </li>
        <li class="boton-agregar">
          <button type="submit">Guardar</button>
        </li>
        <li class="boton-volver">
          <a href="formacion_profesional.php">
            <i class="bi bi-arrow-left-circle"></i> Cancelar
          </a>
        </li>
      </form>
    <?php
        break;

      // ────────────────────────────────────────────
      case 'edit':
      // ────────────────────────────────────────────
        if(!$id){
          header("Location: formacion_profesional.php");
          exit;
        }
        if($_SERVER['REQUEST_METHOD']==='POST'){
          $nombre = trim($_POST['formaciones_profesionales_nombre']);
          if($nombre !== ''){
            mysqli_query($conexion,
              "UPDATE formaciones_profesionales
               SET formaciones_profesionales_nombre='"
               .mysqli_real_escape_string($conexion, $nombre).
               "' WHERE formaciones_profesionales_id='$id'"
            );
            header("Location: formacion_profesional.php");
            exit;
          } else {
            $error = 'Debe ingresar un nombre.';
          }
        } else {
          // precarga
          $row = mysqli_fetch_assoc(mysqli_query(
            $conexion,
            "SELECT formaciones_profesionales_nombre
             FROM formaciones_profesionales
             WHERE formaciones_profesionales_id='$id'"
          ));
          $_POST['formaciones_profesionales_nombre'] = $row['formaciones_profesionales_nombre'] ?? '';
        }
    ?>
      <h1 class="title">Editar Formación Profesional</h1>
      <?php if(!empty($error)): ?>
        <p class="error"><?= $error ?></p>
      <?php endif ?>

      <form method="post" class="botones">
        <li class="boton-agregar">
          <input
            type="text"
            name="formaciones_profesionales_nombre"
            value="<?= htmlspecialchars($_POST['formaciones_profesionales_nombre']) ?>"
            style="width:auto;"
          >
        </li>
        <li class="boton-agregar">
          <button type="submit">Guardar Cambios</button>
        </li>
        <li class="boton-volver">
          <a href="formacion_profesional.php">
            <i class="bi bi-arrow-left-circle"></i> Cancelar
          </a>
        </li>
      </form>
    <?php
        break;

      // ────────────────────────────────────────────
      case 'delete':
      // ────────────────────────────────────────────
        if(!$id){
          header("Location: formacion_profesional.php");
          exit;
        }
        // obtenemos el nombre para mostrar en el título
        $rowDel = mysqli_fetch_assoc(mysqli_query(
          $conexion,
          "SELECT formaciones_profesionales_nombre
           FROM formaciones_profesionales
           WHERE formaciones_profesionales_id='$id'"
        ));
        $nombreDel = $rowDel['formaciones_profesionales_nombre'] ?? '';
        if($_SERVER['REQUEST_METHOD']==='POST'){
          mysqli_query($conexion,
            "UPDATE formaciones_profesionales
             SET formaciones_profesionales_eliminado='1'
             WHERE formaciones_profesionales_id='$id'"
          );
          header("Location: formacion_profesional.php");
          exit;
        }
    ?>
      <h1 class="title">
        Eliminar Formación Profesional de “<?= htmlspecialchars($nombreDel) ?>”
      </h1>
      <form method="post" class="botones">
        <li class="boton-agregar">
          <button type="submit">Sí, eliminar</button>
        </li>
        <li class="boton-volver">
          <a href="formacion_profesional.php">
            <i class="bi bi-arrow-left-circle"></i> No, cancelar
          </a>
        </li>
      </form>
    <?php
        break;

      // ────────────────────────────────────────────
      default:
      // ────────────────────────────────────────────
        $res = mysqli_query(
          $conexion,
          "SELECT formaciones_profesionales_id,
                  formaciones_profesionales_nombre
           FROM formaciones_profesionales
           WHERE formaciones_profesionales_eliminado='0'"
        );
        $counter = 0;
    ?>
      <h1 class="title">Formaciones Profesionales</h1>
      <ul class="botones">
        <li class="boton-agregar">
          <a href="formacion_profesional.php?action=add">
            <i class="bi bi-plus-circle"></i> Agregar
          </a>
        </li>
      </ul>

      <div class="contenedor">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($res)): ?>
            <tr>
              <td><?= ++$counter ?></td>
              <td><?= htmlspecialchars($row['formaciones_profesionales_nombre']) ?></td>
              <td>
                <a href="formacion_profesional.php?action=edit&id=<?= $row['formaciones_profesionales_id'] ?>">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="formacion_profesional.php?action=delete&id=<?= $row['formaciones_profesionales_id'] ?>">
                  <i class="bi bi-trash3" style="color:red;"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endswitch; ?>
  </main>

  <?php include('footer.php'); ?>
</body>
</html>
<?php
  ob_end_flush();
?>
