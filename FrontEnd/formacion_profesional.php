<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// INICIAR BUFFER DE SALIDA LO ANTES POSIBLE
if (ob_get_level() === 0) {
  ob_start();
}

// COMPRACION DE LOGIN, DEJA SESION DISPONIBLE
if (empty($_SESSION['user_id'])) {
}

// LEER ACTION / ID DE LA URL
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? intval($_GET['id']) : null;

// HANDLER AJAX, CHECK CUE, DEVUELVE JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'check_nombre') {

  // LIMPIO BUFFER PARA ASEGURAR QUE EL JSON SEA LA UNICA SALIDA
  while (ob_get_level() > 0) {
    ob_end_clean();
  }

  // RESPUESTA SERA UN JSON UTF-8
  header('Content-Type: application/json; charset=utf-8');

  // SI NO HAY SESION DEVOLVEMOS UN JSCION INDICANDO QUE EXPRIO
  if (empty($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'sin_session']);
    exit;
  }

  // TOMO LOS PARAMETROS
  $nombre = trim($_POST['nombre'] ?? '');
  $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;

  if ($nombre === '') {
    echo json_encode(['ok' => false, 'error' => 'Nombre vacío']);
    exit;
  }

  // CONTAR DUPLICADOS
  if ($exclude_id > 0) {
    $sql = "SELECT COUNT(*) FROM formaciones_profesionales WHERE formaciones_profesionales_nombre = ? AND formaciones_profesionales_eliminado='0' AND formaciones_profesionales_id <> ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "si", $nombre, $exclude_id);
  } else {
    $sql = "SELECT COUNT(*) FROM formaciones_profesionales WHERE formaciones_profesionales_nombre = ? AND formaciones_profesionales_eliminado='0'";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $nombre);
  }

  if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Error en la base de datos (prepare).']);
    exit;
  }

  // EJECUTAR Y DEVOLVER RESULTADOS
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $count);
  mysqli_stmt_fetch($stmt);
  mysqli_stmt_close($stmt);

  echo json_encode(['ok' => true, 'exists' => ($count > 0)]);
  exit;
}

// PROCESO DE ACCIONES POST / GET
$error = null;

// RESTAURACION
if ($action === 'restore' && $id) {
  if (empty($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
  }

  // MARCO COMO NO ELIMINADO
  mysqli_query(
    $conexion,
    "UPDATE formaciones_profesionales
     SET formaciones_profesionales_eliminado='0'
     WHERE formaciones_profesionales_id='" . mysqli_real_escape_string($conexion, $id) . "'"
  );

  // REDIRIGIMOS AL LISTADO
  header("Location: formacion_profesional.php?action=deleted&msg=restaurado");
  exit;
}

// POST AGREGAR, EDITAR, ELIMINAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_SESSION['user_id'])) {
    header('Location: index.php', true, 303);
    exit;
  }

  // AGREGAR
  if ($action === 'add') {
    $nombre = trim($_POST['formaciones_profesionales_nombre'] ?? '');

    // VALIDACIONES
    if ($nombre === '') {
      $error = 'Debe ingresar un nombre.';
    } else {

      // VERIFICAR DUPLICADOS
      $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) FROM formaciones_profesionales WHERE formaciones_profesionales_nombre = ? AND formaciones_profesionales_eliminado='0'");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $nombre);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $cnt);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($cnt > 0) {

          // YA EXISTE
          $error = 'Ya existe una formación con ese nombre.';
        } else {

          // INSERTAR NUEVO
          mysqli_query(
            $conexion,
            "INSERT INTO formaciones_profesionales(formaciones_profesionales_nombre)
             VALUES ('" . mysqli_real_escape_string($conexion, $nombre) . "')"
          );
          header("Location: formacion_profesional.php?msg=guardado");
          exit;
        }
      } else {
        $error = 'Error en la base de datos (prepare).';
      }
    }

    // EDITAR
  } elseif ($action === 'edit' && $id) {
    $nombre = trim($_POST['formaciones_profesionales_nombre'] ?? '');

    // VALIDACIONES
    if ($nombre === '') {
      $error = 'Debe ingresar un nombre.';
    } else {

      // EXCLUIR EL PROPIO ID AL VERIFICAR DUPLICADO
      $ex_id = intval($id);
      $stmt = mysqli_prepare($conexion, "SELECT COUNT(*) FROM formaciones_profesionales WHERE formaciones_profesionales_nombre = ? AND formaciones_profesionales_eliminado='0' AND formaciones_profesionales_id <> ?");
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $nombre, $ex_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $cnt);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($cnt > 0) {
          $error = 'Ya existe otra formación con ese nombre.';

          // ACTUALIZA
        } else {
          mysqli_query(
            $conexion,
            "UPDATE formaciones_profesionales
             SET formaciones_profesionales_nombre='" . mysqli_real_escape_string($conexion, $nombre) . "'
             WHERE formaciones_profesionales_id='" . mysqli_real_escape_string($conexion, $id) . "'"
          );
          header("Location: formacion_profesional.php?msg=editado");
          exit;
        }
      } else {
        $error = 'Error en la base de datos (prepare).';
      }
    }

    // ELIMINAR
  } elseif ($action === 'delete' && $id) {

    // SE MARCA COMO ELIMINADOS
    mysqli_query(
      $conexion,
      "UPDATE formaciones_profesionales
       SET formaciones_profesionales_eliminado='1'
       WHERE formaciones_profesionales_id='" . mysqli_real_escape_string($conexion, $id) . "'"
    );
    header("Location: formacion_profesional.php?msg=eliminado");
    exit;
  }
}

// EVITAR QUE EL NAVEGADOR MUESTRE PAGINAS DESDE CACHE
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// SI NO HAY SESION REDIRIGIMOS AL LOGEO
if (empty($_SESSION['user_id'])) {
  header('Location: index.php', true, 303);
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

<body>
  <main class="fp-page">
    <?php switch ($action):

        // AGREGAR
      case 'add':
        $_POST['formaciones_profesionales_nombre'] = $_POST['formaciones_profesionales_nombre'] ?? '';
    ?>

        <h1 class="title">Agregar Formación Profesional</h1>

        <!-- CONTENEDOR DE LOS MENSAJES DE ERROR -->
        <div id="formacionProfesionalFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
          <?php if (!empty($error)): ?>
            <p class="msg"><?= htmlspecialchars($error) ?></p>
          <?php endif ?>
        </div>

        <form id="escuelaForm" method="post" class="botones">

          <!-- VALIDACION DE DUPLICADOS -->
          <input type="hidden" name="exclude_id" value="0">

          <!-- FORMULARIO -->
          <li class="boton-agregar">
            <div class="input-label">Nombre</div>
            <input name="formaciones_profesionales_nombre" id="formaciones_profesionales_nombre" placeholder="Nombre"
              value="<?= htmlspecialchars($_POST['formaciones_profesionales_nombre'] ?? '') ?>">
          </li>

          <!-- BOTONES -->
          <li class="boton-agregar"><button type="submit">Guardar</button></li>
          <li class="boton-volver">
            <a href="formacion_profesional.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
          </li>
        </form>
      <?php
        break;

      // EDITAR
      case 'edit':

        // SI NO HAY ID VALIDO REDIRIGIMOS
        if (!$id) {
          header("Location: formacion_profesional.php");
          exit;
        }

        // CARGAMOS DESDE LA BASE DE DATOS
        if (!isset($_POST['formaciones_profesionales_nombre'])) {
          $r = mysqli_fetch_assoc(mysqli_query($conexion, "
            SELECT formaciones_profesionales_nombre
            FROM formaciones_profesionales
            WHERE formaciones_profesionales_id='" . mysqli_real_escape_string($conexion, $id) . "'
          "));
          if ($r) {
            $_POST['formaciones_profesionales_nombre'] = $r['formaciones_profesionales_nombre'];
          } else {
            echo "<p>Error al cargar datos.</p>";
            break;
          }
        }
      ?>

        <h1 class="title">Editar Formación Profesional</h1>

        <div id="formacionProfesionalFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
          <?php if (!empty($error)): ?>
            <p class="msg"><?= htmlspecialchars($error) ?></p>
          <?php endif ?>
        </div>

        <form id="escuelaForm" method="post" class="botones">

          <!-- EXCLUIR EL PROPIO ID AL VERIFICAR DUPLICADO -->
          <input type="hidden" name="exclude_id" value="<?= intval($id) ?>">

          <!-- FORMULARIO -->
          <li class="boton-agregar">
            <div class="input-label">Nombre</div>
            <input id="formaciones_profesionales_nombre" name="formaciones_profesionales_nombre" placeholder="Nombre"
              value="<?= htmlspecialchars($_POST['formaciones_profesionales_nombre'] ?? '') ?>">
          </li>

          <!-- BOTONES -->
          <li class="boton-agregar"><button type="submit">Guardar Cambios</button></li>
          <li class="boton-volver">
            <a href="formacion_profesional.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
          </li>
        </form>
      <?php
        break;

      // ELIMINAR
      case 'delete':
        if (!$id) {
          header("Location: formacion_profesional.php");
          exit;
        }

        // CARGAR DATOS
        $rowDel = mysqli_fetch_assoc(mysqli_query($conexion, "
          SELECT formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_id='" . mysqli_real_escape_string($conexion, $id) . "'
        "));
        $nombreDel = $rowDel['formaciones_profesionales_nombre'] ?? '';
      ?>

        <h1 class="title">Eliminar Formación Profesional de “<?= htmlspecialchars($nombreDel) ?>”</h1>

        <div id="formacionProfesionalFormError" class="error-box <?php if (!empty($error)) echo 'visible'; ?>" role="status" aria-live="polite" aria-atomic="true">
          <?php if (!empty($error)): ?>
            <p class="msg"><?= htmlspecialchars($error) ?></p>
          <?php endif ?>
        </div>

        <!-- BOTONES DE CONFIRMACION -->
        <form method="post" class="botones">
          <li class="boton-agregar"><button type="submit">Sí, eliminar</button></li>
          <li class="boton-volver"><a href="formacion_profesional.php"><i class="bi bi-arrow-left-circle"></i> No, cancelar</a></li>
        </form>
      <?php
        break;

      // MOSTRAR ELIMINADOS
      case 'deleted':

        // CARGAR DATOS
        $res = mysqli_query($conexion, "
          SELECT formaciones_profesionales_id, formaciones_profesionales_nombre
          FROM formaciones_profesionales
          WHERE formaciones_profesionales_eliminado='1'
        ");
        $counter = 0;
      ?>

        <h1 class="title">Formaciones Profesionales Eliminadas</h1>

        <!-- BOTONES -->
        <ul class="botones">
          <li class="boton-volver"><a href="formacion_profesional.php"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a></li>
        </ul>

        <!-- TABLA -->
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
              <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                  <td><?= ++$counter ?></td>
                  <td><?= htmlspecialchars($row['formaciones_profesionales_nombre']) ?></td>
                  <td>
                    <a href="formacion_profesional.php?action=restore&id=<?= $row['formaciones_profesionales_id'] ?>" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php
        break;

      // LISTA DE ACTIVOS
      default:

        // CARGAR DATOS
        $res = mysqli_query($conexion, "
          SELECT formaciones_profesionales_id, formaciones_profesionales_nombre
          FROM formaciones_profesionales
          WHERE formaciones_profesionales_eliminado='0'
        ");
        $counter = 0;
      ?>

        <h1 class="title">Formaciones Profesionales</h1>

        <!-- BOTONES -->
        <ul class="botones">
          <li class="boton-agregar"><a href="formacion_profesional.php?action=add"><i class="bi bi-plus-circle"></i> Agregar</a></li>
          <li class="boton-volver"><a href="formacion_profesional.php?action=deleted"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a></li>
        </ul>

        <!-- TABLA -->
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
              <?php while ($row = mysqli_fetch_assoc($res)): ?>
                <tr>
                  <td><?= ++$counter ?></td>
                  <td><?= htmlspecialchars($row['formaciones_profesionales_nombre']) ?></td>
                  <td>
                    <a href="formacion_profesional.php?action=edit&id=<?= $row['formaciones_profesionales_id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a href="formacion_profesional.php?action=delete&id=<?= $row['formaciones_profesionales_id'] ?>" title="Eliminar"><i class="bi bi-trash3" style="color:red;"></i></a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
    <?php endswitch; ?>

  </main>

  <!-- FOOTER -->
  <?php include('footer.php'); ?>

  <!-- SWEETALERT -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    // MOSTRAR MENSAJES DE SWEETALERT
    document.addEventListener('DOMContentLoaded', function() {
      const params = new URLSearchParams(window.location.search);
      const msg = params.get('msg');
      const items = {
        guardado: {
          icon: 'success',
          title: 'Guardado',
          text: 'La formación fue creada correctamente.'
        },
        editado: {
          icon: 'success',
          title: 'Actualizado',
          text: 'Los cambios fueron guardados.'
        },
        eliminado: {
          icon: 'success',
          title: 'Eliminado',
          text: 'La formación fue eliminada.'
        },
        restaurado: {
          icon: 'success',
          title: 'Restaurado',
          text: 'La formación fue restaurada.'
        }
      };
      if (msg && items[msg]) {
        Swal.fire(items[msg]);
      }
      if (msg) {
        params.delete('msg');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
      }
    });
  </script>

  <!-- SCRIPTS -->
  <script src="JS/validaciones_formacion_profesional.js" defer></script>

</body>

</html>
<?php

// ENVIAMOS BUFFER AL CLIENTE
if (ob_get_level() > 0) {
  ob_end_flush();
}

?>