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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'check_cue') {

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
  $cue = $_POST['cue'] ?? '';
  $exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;

  if ($cue === '' || !ctype_digit($cue)) {
    echo json_encode(['ok' => false, 'error' => 'CUE inválido (debe ser numérico).']);
    exit;
  }

  // CONTAR DUPLICADOS
  if ($exclude_id > 0) {
    $sql = "SELECT COUNT(*) FROM escuelas WHERE escuelas_cue = ? AND escuelas_eliminado='0' AND escuelas_id <> ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "si", $cue, $exclude_id);
  } else {
    $sql = "SELECT COUNT(*) FROM escuelas WHERE escuelas_cue = ? AND escuelas_eliminado='0'";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $cue);
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

  // OBTENER DOMICILIOS SEGUN ID
  $row = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT domicilios_id FROM escuelas WHERE escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
  "));
  $domId = $row['domicilios_id'] ?? 0;

  // MARCO LA ESCUELA COMO NO ELIMINADA
  mysqli_query($conexion, "
    UPDATE escuelas SET escuelas_eliminado='0' WHERE escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
  ");
  // RESTAURO TAMBIEN DOMICILIO SEGUN ID
  if ($domId) {
    mysqli_query($conexion, "
      UPDATE domicilios SET domicilios_eliminado='0' WHERE domicilios_id='" . mysqli_real_escape_string($conexion, $domId) . "'
    ");
  }
  // REDIRIGIMOS AL LISTADO
  header("Location: escuelas.php?action=deleted&msg=restaurado");
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
    $nombre    = trim($_POST['escuelas_nombre'] ?? '');
    $cue       = trim($_POST['escuelas_cue'] ?? '');
    $domreal   = trim($_POST['domicilio_calle'] ?? '');
    $mapsearch = trim($_POST['map_search'] ?? '');
    $lat       = trim($_POST['domicilio_lat'] ?? '');
    $lng       = trim($_POST['domicilio_lng'] ?? '');

    // VALIDACIONES
    if ($nombre === '') {
      $error = 'Debe ingresar un nombre.';
    } elseif ($cue === '' || !ctype_digit($cue)) {
      $error = 'Debe ingresar un CUE numérico.';
    } elseif ($domreal === '') {
      $error = 'Debe ingresar la dirección real.';
    } elseif ($mapsearch === '') {
      $error = 'Debes ingresar texto para el mapa.';
    } elseif ($lat === '' || $lng === '') {
      $error = 'Espera a que se calculen las coordenadas.';
    } else {

      // VERIFICAR DUPLICADOS DEL CUE
      $sqlChk = "SELECT COUNT(*) FROM escuelas WHERE escuelas_cue = ? AND escuelas_eliminado='0'";
      $stmtChk = mysqli_prepare($conexion, $sqlChk);
      if ($stmtChk) {
        mysqli_stmt_bind_param($stmtChk, "s", $cue);
        mysqli_stmt_execute($stmtChk);
        mysqli_stmt_bind_result($stmtChk, $countChk);
        mysqli_stmt_fetch($stmtChk);
        mysqli_stmt_close($stmtChk);
      } else {
        $error = 'Error en la base de datos (prepare).';
      }

      // SI NO HAY ERROR COMPROBAMOS EL CONTEO
      if (empty($error)) {
        if ($countChk > 0) {
          $error = "Ya existe una escuela con el CUE indicado.";
        } else {

          // INSERTAR DOMICILIO
          $sqlDom = "
            INSERT INTO domicilios
              (domicilios_calle, domicilios_latitud, domicilios_longitud)
            VALUES (
              '" . mysqli_real_escape_string($conexion, $domreal) . "',
              '" . mysqli_real_escape_string($conexion, $lat) . "',
              '" . mysqli_real_escape_string($conexion, $lng) . "'
            )";
          if (!mysqli_query($conexion, $sqlDom)) {
            $error = "Error domicilio: " . mysqli_error($conexion);
          } else {
            $domId = mysqli_insert_id($conexion);

            // INSERTAR ESCUELA
            $sqlEsc = "
              INSERT INTO escuelas
                (escuelas_nombre, escuelas_cue, domicilios_id)
              VALUES (
                '" . mysqli_real_escape_string($conexion, $nombre) . "',
                '" . mysqli_real_escape_string($conexion, $cue) . "',
                '" . mysqli_real_escape_string($conexion, $domId) . "'
              )";
            if (!mysqli_query($conexion, $sqlEsc)) {
              $error = "Error escuela: " . mysqli_error($conexion);
            } else {
              header("Location: escuelas.php?msg=guardado");
              exit;
            }
          }
        }
      }
    }

    // EDITAR
  } elseif ($action === 'edit' && $id) {
    $nombre       = trim($_POST['escuelas_nombre'] ?? '');
    $cue          = trim($_POST['escuelas_cue'] ?? '');
    $domreal      = trim($_POST['domicilio_calle'] ?? '');
    $mapsearch    = trim($_POST['map_search'] ?? '');
    $lat          = trim($_POST['domicilio_lat'] ?? '');
    $lng          = trim($_POST['domicilio_lng'] ?? '');
    $domiciliosId = intval($_POST['domicilios_id'] ?? 0);

    // VALIDACIONES
    if ($nombre === '') {
      $error = 'Debe ingresar un nombre.';
    } elseif ($cue === '' || !ctype_digit($cue)) {
      $error = 'Debe ingresar un CUE numérico.';
    } elseif ($domreal === '') {
      $error = 'Debe ingresar la dirección real.';
    } elseif ($mapsearch === '') {
      $error = 'Debes ingresar texto para el mapa.';
    } elseif ($lat === '' || $lng === '') {
      $error = 'Espera a que se calculen las coordenadas.';
    } else {

      // COMPROBAMOS DUPLICADOS DE CUE
      $ex_id = intval($id);
      $sqlChk = "SELECT COUNT(*) FROM escuelas WHERE escuelas_cue = ? AND escuelas_eliminado='0' AND escuelas_id <> ?";
      $stmtChk = mysqli_prepare($conexion, $sqlChk);
      if ($stmtChk) {
        mysqli_stmt_bind_param($stmtChk, "si", $cue, $ex_id);
        mysqli_stmt_execute($stmtChk);
        mysqli_stmt_bind_result($stmtChk, $countChk);
        mysqli_stmt_fetch($stmtChk);
        mysqli_stmt_close($stmtChk);
      } else {
        $error = 'Error en la base de datos (prepare).';
      }

      if (empty($error)) {
        if ($countChk > 0) {
          $error = "Ya existe otra escuela con el mismo CUE.";
        } else {

          // ACTUALIZAR DOMICILIO
          $updDom = "
            UPDATE domicilios SET
              domicilios_calle   = '" . mysqli_real_escape_string($conexion, $domreal) . "',
              domicilios_latitud = '" . mysqli_real_escape_string($conexion, $lat) . "',
              domicilios_longitud= '" . mysqli_real_escape_string($conexion, $lng) . "'
            WHERE domicilios_id = '" . mysqli_real_escape_string($conexion, $domiciliosId) . "'
          ";
          mysqli_query($conexion, $updDom);
          if (mysqli_errno($conexion)) {
            $error = "Error al actualizar domicilio: " . mysqli_error($conexion);
          } else {

            // ACTUALIZAR ESCUELA
            $updEsc = "
              UPDATE escuelas SET
                escuelas_nombre = '" . mysqli_real_escape_string($conexion, $nombre) . "',
                escuelas_cue    = '" . mysqli_real_escape_string($conexion, $cue) . "'
              WHERE escuelas_id = '" . mysqli_real_escape_string($conexion, $id) . "'
            ";
            mysqli_query($conexion, $updEsc);
            if (mysqli_errno($conexion)) {
              $error = "Error al actualizar escuela: " . mysqli_error($conexion);
            } else {
              header("Location: escuelas.php?msg=editado");
              exit;
            }
          }
        }
      }
    }

    // ELIMINAR
  } elseif ($action === 'delete' && $id) {

    // SE MARCA LA ESCUELA Y SU DOMICILIO COMO ELIMINADOS
    mysqli_query($conexion, "
      UPDATE escuelas SET escuelas_eliminado='1' WHERE escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
    ");
    $domIdRow = mysqli_fetch_row(mysqli_query($conexion, "
      SELECT domicilios_id FROM escuelas WHERE escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
    "));
    $domId = $domIdRow[0] ?? 0;
    if ($domId) {
      mysqli_query($conexion, "
        UPDATE domicilios SET domicilios_eliminado='1' WHERE domicilios_id='" . mysqli_real_escape_string($conexion, $domId) . "'
      ");
    }
    header("Location: escuelas.php?msg=eliminado");
    exit;
  }
}

// RENDERIZADO GET
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
        $_POST['escuelas_nombre']  = $_POST['escuelas_nombre']  ?? '';
        $_POST['escuelas_cue']     = $_POST['escuelas_cue']     ?? '';
        $_POST['domicilio_calle']  = $_POST['domicilio_calle']  ?? '';
        $_POST['map_search']       = $_POST['map_search']       ?? '';
        $_POST['domicilio_lat']    = $_POST['domicilio_lat']    ?? '';
        $_POST['domicilio_lng']    = $_POST['domicilio_lng']    ?? '';
    ?>

        <h1 class="title">Agregar Escuela</h1>

        <!-- CONTENEDOR DE LOS MENSAJES DE ERROR -->
        <div id="escuelaFormError" class="error-box" role="status" aria-live="polite" aria-atomic="true">
          <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?>
            </p><?php endif ?>
        </div>

        <!-- FORMULARIO AGREGAR -->
        <form id="escuelaForm" method="post" class="botones">
          <li class="boton-agregar">
            <div class="input-label">Nombre</div>
            <input name="escuelas_nombre" id="escuelas_nombre" placeholder="Nombre"
              value="<?= htmlspecialchars($_POST['escuelas_nombre'] ?? '') ?>">
          </li>

          <li class="boton-agregar">
            <div class="input-label">CUE</div>
            <input name="escuelas_cue" id="escuelas_cue" placeholder="CUE"
              value="<?= htmlspecialchars($_POST['escuelas_cue'] ?? '') ?>">
          </li>

          <li class="boton-agregar" style="flex:2;">
            <div class="input-label">Dirección Real</div>
            <input name="domicilio_calle" id="domicilio_calle"
              placeholder="Calle y número reales"
              value="<?= htmlspecialchars($_POST['domicilio_calle'] ?? '') ?>"
              style="width:100%;">
          </li>

          <!-- BUSCAR MAPA -->
          <li class="boton-agregar" style="flex:2; display:flex; align-items:flex-start; gap:8px;">
            <div style="flex:1;">
              <div class="input-label">Buscar en Mapa</div>
              <div style="display:flex; gap:8px; align-items:center;">
                <input id="map_search" name="map_search" placeholder="Buscar en el mapa"
                  value="<?= htmlspecialchars($_POST['map_search'] ?? '') ?>" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc;">
                <button type="button" id="btn_map_search" class="btn-search">Buscar</button>
              </div>
            </div>
          </li>

          <input type="hidden" name="domicilio_lat" id="domicilio_lat" value="<?= htmlspecialchars($_POST['domicilio_lat'] ?? '') ?>">
          <input type="hidden" name="domicilio_lng" id="domicilio_lng" value="<?= htmlspecialchars($_POST['domicilio_lng'] ?? '') ?>">

          <!-- CONTENEDOR DEL MAPA -->
          <div id="map" style="height:300px; width:100%; margin:1rem 0;"></div>

          <!-- BOTONES -->
          <li class="boton-agregar"><button type="submit">Guardar</button></li>
          <li class="boton-volver">
            <a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
          </li>
        </form>
      <?php
        break;

      // EDITAR
      case 'edit':
        if (!$id) {
          header("Location: escuelas.php");
          exit;
        }

        // CARGO LOS DATOS
        if (!isset($_POST['escuelas_nombre'])) {
          $r = mysqli_fetch_assoc(mysqli_query($conexion, "
            SELECT e.escuelas_nombre, e.escuelas_cue, d.domicilios_id, d.domicilios_calle, d.domicilios_latitud, d.domicilios_longitud
            FROM escuelas e
            LEFT JOIN domicilios d ON e.domicilios_id = d.domicilios_id
            WHERE e.escuelas_id = '" . mysqli_real_escape_string($conexion, $id) . "'
          "));
          if ($r) {
            $_POST['escuelas_nombre'] = $r['escuelas_nombre'];
            $_POST['escuelas_cue'] = $r['escuelas_cue'];
            $_POST['domicilio_calle'] = $r['domicilios_calle'];
            $_POST['map_search'] = $r['domicilios_calle'];
            $_POST['domicilio_lat'] = $r['domicilios_latitud'];
            $_POST['domicilio_lng'] = $r['domicilios_longitud'];
            $_POST['domicilios_id'] = $r['domicilios_id'];
          } else {
            echo "<p>Error al cargar datos.</p>";
            break;
          }
        }
      ?>

        <h1 class="title">Editar Escuela</h1>

        <!-- CONTENEDOR DE LOS MENSAJES DE ERROR -->
        <div id="escuelaFormError" class="error-box" role="status" aria-live="polite" aria-atomic="true">
          <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?>
            </p><?php endif ?>
        </div>

        <!-- FORMULARIO EDITAR -->
        <form id="escuelaForm" method="post" class="botones">
          <li class="boton-agregar">
            <div class="input-label">Nombre</div>
            <input id="escuelas_nombre" name="escuelas_nombre" placeholder="Nombre"
              value="<?= htmlspecialchars($_POST['escuelas_nombre'] ?? '') ?>">
          </li>

          <li class="boton-agregar">
            <div class="input-label">CUE</div>
            <input id="escuelas_cue" name="escuelas_cue" placeholder="CUE"
              value="<?= htmlspecialchars($_POST['escuelas_cue'] ?? '') ?>">
          </li>

          <li class="boton-agregar" style="flex:2;">
            <div class="input-label">Dirección real</div>
            <input name="domicilio_calle" id="domicilio_calle"
              placeholder="Calle y número reales"
              value="<?= htmlspecialchars($_POST['domicilio_calle'] ?? '') ?>"
              style="width:100%;">
          </li>

          <!-- BUSCAR MAPA -->
          <li class="boton-agregar" style="flex:2; display:flex; align-items:flex-start; gap:8px;">
            <div style="flex:1;">
              <div class="input-label">Buscar en Mapa</div>
              <div style="display:flex; gap:8px; align-items:center;">
                <input id="map_search" name="map_search" placeholder="Buscar en el mapa"
                  value="<?= htmlspecialchars($_POST['map_search'] ?? '') ?>" style="width:100%; padding:8px 10px; border-radius:6px; border:1px solid #ccc;">
                <button type="button" id="btn_map_search" class="btn-search">Buscar</button>
              </div>
            </div>
          </li>

          <input type="hidden" id="escuelas_id" name="escuelas_id" value="<?= intval($id) ?>">
          <input type="hidden" name="domicilios_id" id="domicilios_id" value="<?= htmlspecialchars($_POST['domicilios_id'] ?? '') ?>">
          <input type="hidden" name="domicilio_lat" id="domicilio_lat" value="<?= htmlspecialchars($_POST['domicilio_lat'] ?? '') ?>">
          <input type="hidden" name="domicilio_lng" id="domicilio_lng" value="<?= htmlspecialchars($_POST['domicilio_lng'] ?? '') ?>">

          <!-- CONTENEDOR DEL MAPA -->
          <div id="map" style="height:300px; width:100%; margin:1rem 0;"></div>

          <!-- BOTONES -->
          <li class="boton-agregar"><button type="submit">Guardar Cambios</button></li>
          <li class="boton-volver">
            <a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Cancelar</a>
          </li>
        </form>
      <?php
        break;

      // ELIMINAR
      case 'delete':
        if (!$id) {
          header("Location: escuelas.php");
          exit;
        }

        // CARGO LOS DATOS
        $r2 = mysqli_fetch_assoc(mysqli_query($conexion, "
          SELECT e.escuelas_nombre AS Nombre, e.escuelas_cue AS CUE, d.domicilios_calle AS Domicilio
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
        "));
      ?>

        <h1 class="title">Eliminar Escuela “<?= htmlspecialchars($r2['Nombre'] ?? '') ?>”</h1>
        <div class="contenedor">
          <table class="table">
            <tr>
              <th style="width:30%;text-align:left;">Nombre</th>
              <td><?= htmlspecialchars($r2['Nombre'] ?? '') ?></td>
            </tr>
            <tr>
              <th style="text-align:left;">CUE</th>
              <td><?= htmlspecialchars($r2['CUE'] ?? '') ?></td>
            </tr>
            <tr>
              <th style="text-align:left;">Domicilio</th>
              <td><?= htmlspecialchars($r2['Domicilio'] ?? '') ?></td>
            </tr>
          </table>
        </div>

        <!-- FORMULARIO DE CONFIRMACION -->
        <form method="post" class="botones">
          <li class="boton-agregar"><button type="submit">Sí, eliminar</button></li>
          <li class="boton-volver"><a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> No, cancelar</a></li>
        </form>
      <?php
        break;

        // VISTA
      case 'view':
        if (!$id) {
          header("Location: escuelas.php");
          exit;
        }

        // CARGO LOS DATOS
        $r2 = mysqli_fetch_assoc(mysqli_query($conexion, "
          SELECT e.escuelas_nombre AS Nombre, e.escuelas_cue AS CUE, d.domicilios_calle AS Domicilio, d.domicilios_latitud AS Latitud, d.domicilios_longitud AS Longitud
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_id='" . mysqli_real_escape_string($conexion, $id) . "'
        "));
      ?>

        <h1 class="title">Detalles de “<?= htmlspecialchars($r2['Nombre'] ?? '') ?>”</h1>
        <div class="contenedor">
          <table class="table">
            <tr>
              <th style="width:30%;text-align:left;">Nombre</th>
              <td><?= htmlspecialchars($r2['Nombre'] ?? '') ?></td>
            </tr>
            <tr>
              <th style="text-align:left;">CUE</th>
              <td><?= htmlspecialchars($r2['CUE'] ?? '') ?></td>
            </tr>
            <tr>
              <th style="text-align:left;">Domicilio</th>
              <td><?= htmlspecialchars($r2['Domicilio'] ?? '') ?></td>
            </tr>
          </table>
          <?php if (!empty($r2['Latitud']) && !empty($r2['Longitud'])): ?>
            <div style="margin-top:1.5rem;">
              <iframe width="100%" height="350" src="https://maps.google.com/maps?q=<?= htmlspecialchars($r2['Latitud']) ?>,<?= htmlspecialchars($r2['Longitud']) ?>&z=15&output=embed" style="border:0;" loading="lazy"></iframe>
            </div>
          <?php endif; ?>
        </div>
      <?php
        break;

        // ELIMINAR
      case 'deleted':
        $res = mysqli_query($conexion, "
          SELECT e.escuelas_id, e.escuelas_nombre, e.escuelas_cue, d.domicilios_id
          FROM escuelas e
          LEFT JOIN domicilios d ON e.domicilios_id=d.domicilios_id
          WHERE e.escuelas_eliminado='1'
        ");
        $cnt = 0;
      ?>

        <h1 class="title">Escuelas Eliminadas</h1>
        <ul class="botones">
          <li class="boton-volver"><a href="escuelas.php"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a></li>
        </ul>
        <div class="contenedor">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>CUE</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($rw = mysqli_fetch_assoc($res)): ?>
                <tr>
                  <td><?= ++$cnt ?></td>
                  <td><?= htmlspecialchars($rw['escuelas_nombre']) ?></td>
                  <td><?= htmlspecialchars($rw['escuelas_cue']) ?></td>
                  <td>
                    <a href="escuelas.php?action=restore&id=<?= $rw['escuelas_id'] ?>" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php
        break;

        // LISTA DE ESCUELAS ACTIVAS
      default:
        $res = mysqli_query($conexion, "
          SELECT escuelas_id, escuelas_nombre, escuelas_cue
          FROM escuelas
          WHERE escuelas_eliminado='0'
        ");
        $cnt = 0;
      ?>
        <h1 class="title">Escuelas</h1>
        <ul class="botones">
          <li class="boton-agregar"><a href="escuelas.php?action=add"><i class="bi bi-plus-circle"></i> Agregar</a></li>
          <li class="boton-volver"><a href="escuelas.php?action=deleted"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a></li>
        </ul>
        <div class="contenedor">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Nombre</th>
                <th>CUE</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($rw = mysqli_fetch_assoc($res)): ?>
                <tr>
                  <td><?= ++$cnt ?></td>
                  <td><?= htmlspecialchars($rw['escuelas_nombre']) ?></td>
                  <td><?= htmlspecialchars($rw['escuelas_cue']) ?></td>
                  
                  <!-- ACCION BOTONES VER, EDITAR, ELIMINAR -->
                  <td>
                    <a href="escuelas.php?action=view&id=<?= $rw['escuelas_id'] ?>" title="Ver Más"><i class="bi bi-eye"></i></a>
                    <a href="escuelas.php?action=edit&id=<?= $rw['escuelas_id'] ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a href="escuelas.php?action=delete&id=<?= $rw['escuelas_id'] ?>" title="Eliminar"><i class="bi bi-trash3" style="color:red;"></i></a>
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

  <!-- SCRIPTS -->
  <script src="JS/escuelas.js" defer></script>
  <script src="JS/validaciones_escuelas.js" defer></script>

</body>

</html>

<?php

// ENVIAMOS BUFFER AL CLIENTE
if (ob_get_level() > 0) {
  ob_end_flush();
}

?>