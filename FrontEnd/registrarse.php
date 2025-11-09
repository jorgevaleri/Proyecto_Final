<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// MUESTRAS LOS ERRORES EN DESARROLLO
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ENDPOINT PARA CHEQUEAR DNI
if (isset($_GET['action']) && $_GET['action'] === 'check_dni') {
  while (ob_get_level()) ob_end_clean();

  header('Content-Type: application/json; charset=utf-8');

  $dni = trim($_GET['dni'] ?? '');
  if ($dni === '') {
    http_response_code(400);
    echo json_encode(['error' => 'DNI no proporcionado']);
    exit;
  }

  // NORMALIZAR DNI
  $dniClean = preg_replace('/\D/', '', $dni);

  // BUSCAR PERSONA POR DNI
  $stmt = mysqli_prepare($conexion, "SELECT personas_id, personas_apellido, personas_nombre, personas_fechnac, personas_sexo FROM personas WHERE personas_dni = ? AND personas_eliminado = 0 LIMIT 1");
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Error DB prepare personas']);
    exit;
  }
  mysqli_stmt_bind_param($stmt, 's', $dniClean);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_store_result($stmt);
  $exists_persona = (mysqli_stmt_num_rows($stmt) > 0);
  $persona = null;
  $pid = null;
  if ($exists_persona) {
    mysqli_stmt_bind_result($stmt, $pid, $pape, $pnom, $pfn, $psex);
    mysqli_stmt_fetch($stmt);
    $persona = [
      'id' => (int)$pid,
      'apellido' => $pape,
      'nombre' => $pnom,
      'fechnac' => $pfn,
      'sexo' => $psex
    ];
  }
  mysqli_stmt_close($stmt);

  $has_usuario = false;
  if ($exists_persona) {
    // VERIFICAR SI EXISTE USUARIO ACTIVO
    $stmt2 = mysqli_prepare($conexion, "SELECT usuarios_id FROM usuarios WHERE personas_id = ? AND usuarios_eliminado = 0 LIMIT 1");
    if ($stmt2) {
      mysqli_stmt_bind_param($stmt2, 'i', $pid);
      mysqli_stmt_execute($stmt2);
      mysqli_stmt_store_result($stmt2);
      $has_usuario = (mysqli_stmt_num_rows($stmt2) > 0);
      mysqli_stmt_close($stmt2);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error DB prepare usuarios']);
      exit;
    }
  }

  // ENVIAR JSON
  echo json_encode([
    'exists_persona' => $exists_persona ? true : false,
    'has_usuario' => $has_usuario ? true : false,
    'persona' => $persona
  ]);
  exit;
}

// ENDPOINT GEOCODE
if (isset($_GET['action']) && $_GET['action'] === 'geocode') {

  // LIMPIAR BUFFER
  while (ob_get_level()) ob_end_clean();

  header('Content-Type: application/json; charset=utf-8');

  $q = trim($_GET['q'] ?? '');
  if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No query provided']);
    exit;
  }

  $geocode_email = 'tu-email@ejemplo.com';

  $endpoint = 'https://nominatim.openstreetmap.org/search';
  $params = http_build_query([
    'format' => 'json',
    'q' => $q,
    'addressdetails' => 1,
    'limit' => 6,
  ]);
  $url = $endpoint . '?' . $params;

  // USAMOS CURL PARA HACER LA PETICION EXTERNA
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 8);
  $headers = [
    'User-Agent: MiApp-Registro/1.0 (' . $geocode_email . ')',
    'Accept-Language: es',
  ];
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $res = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  if ($res === false || $http < 200 || $http >= 300) {
    http_response_code(502);
    echo json_encode(['error' => 'Geocoding service error', 'details' => $err]);
    exit;
  }

  // DECODIFICAR JSON
  $decoded = json_decode($res, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    $preview = substr($res, 0, 1200);
    echo json_encode(['error' => 'Invalid geocode response (not JSON)', 'raw_preview' => $preview]);
    exit;
  }

  echo json_encode($decoded);
  exit;
}

// POST PROCESAMIENTO DE FORM
$errors = [];
$show_step = 1;
$registered = false;

// CARGAR ESCUELAS / FORMACION
$escuelas = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado=0 ORDER BY escuelas_nombre");

$formaciones = mysqli_query($conexion, "SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_eliminado=0 ORDER BY formaciones_profesionales_nombre");

// SI SE ENVIA EL FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['step'] ?? '') === 'final') {
  $ape = trim($_POST['personas_apellido'] ?? '');
  $nom = trim($_POST['personas_nombre'] ?? '');
  $dni = trim($_POST['personas_dni'] ?? '');
  $fn  = trim($_POST['personas_fechnac'] ?? '');
  $sex = trim($_POST['personas_sexo'] ?? '');

  $inst_tipo = trim($_POST['inst_tipo'] ?? '');
  $inst_escuela = (int)($_POST['inst_escuela'] ?? 0);
  $inst_formacion = (int)($_POST['inst_formacion'] ?? 0);

  $telefonos_numero = $_POST['telefonos_numero'] ?? [];
  $telefonos_descripcion = $_POST['telefonos_descripcion'] ?? [];
  $telefonos_pred = isset($_POST['telefonos_predeterminado']) ? (int)$_POST['telefonos_predeterminado'] : 0;

  $domicilios_calle = $_POST['domicilios_calle'] ?? [];
  $domicilios_descripcion = $_POST['domicilios_descripcion'] ?? [];
  $domicilios_lat = $_POST['domicilios_latitud'] ?? [];
  $domicilios_lng = $_POST['domicilios_longitud'] ?? [];
  $domicilios_pred = isset($_POST['domicilios_predeterminado']) ? (int)$_POST['domicilios_predeterminado'] : 0;

  $email = trim($_POST['usuarios_email'] ?? '');
  $pass = $_POST['usuarios_clave'] ?? '';
  $pass_conf = $_POST['usuarios_clave_conf'] ?? '';
  $rol = $_POST['usuarios_rol'] ?? 'DOCENTE';

  try {
    // VALIDAR, EVITAR DUPLICADOS POR DNI
    $reuse_persona_id = null;

    $dniClean = preg_replace('/\D/', '', $dni);

    if ($dniClean !== '') {

      // BUSCAR PERSONA POR DNI
      $stmtCheck = mysqli_prepare($conexion, "SELECT personas_id, personas_apellido, personas_nombre FROM personas WHERE personas_dni = ? AND personas_eliminado = 0 LIMIT 1");
      if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, 's', $dniClean);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        if (mysqli_stmt_num_rows($stmtCheck) > 0) {

          // EXISTE PERSONA OBTENER ID
          mysqli_stmt_bind_result($stmtCheck, $existing_persona_id, $existing_apellido, $existing_nombre);
          mysqli_stmt_fetch($stmtCheck);
          mysqli_stmt_close($stmtCheck);

          // VERIFICAR SI YA TIENE USUARIO
          $stmtUserCheck = mysqli_prepare($conexion, "SELECT usuarios_id FROM usuarios WHERE personas_id = ? AND usuarios_eliminado = 0 LIMIT 1");
          if ($stmtUserCheck) {
            mysqli_stmt_bind_param($stmtUserCheck, 'i', $existing_persona_id);
            mysqli_stmt_execute($stmtUserCheck);
            mysqli_stmt_store_result($stmtUserCheck);
            if (mysqli_stmt_num_rows($stmtUserCheck) > 0) {

              // YA TIENE USUARIO, SE BLOQUEA CREACION DUPLICADA
              mysqli_stmt_close($stmtUserCheck);
              $errors[] = 'Esa persona ya tiene un usuario registrado. Si olvidaste la contraseña, recuperala desde Logeo.';
              $show_step = 2;
            } else {

              // EXISTE PERSONA PERO SIN USUARIO
              mysqli_stmt_close($stmtUserCheck);
              $reuse_persona_id = (int)$existing_persona_id;
            }
          } else {
            $errors[] = 'Error al verificar usuario existente (DB).';
            $show_step = 2;
          }
        } else {
          mysqli_stmt_close($stmtCheck);

          // NO EXISTE PERSONA, SEGUIMOS PARA CREAR TODO
        }
      } else {
        $errors[] = 'Error al verificar DNI en la base de datos.';
        $show_step = 2;
      }
    }

    // SI HAY ERRORES, NO SE ESCRIBE
    if (!empty($errors)) {

      // MOSTRAR FORMULARIO CON ERRORES
      $show_step = 2;
    } else {

      mysqli_begin_transaction($conexion);

      if (!empty($reuse_persona_id)) {
        $persona_id = (int)$reuse_persona_id;
      } else {

        // INSERTAR PERSONA NUEVA, DNI NORMALIZADO
        $stmtP = mysqli_prepare($conexion, "INSERT INTO personas (personas_apellido, personas_nombre, personas_dni, personas_fechnac, personas_sexo, personas_eliminado) VALUES (?, ?, ?, ?, ?, 0)");
        if (!$stmtP) throw new Exception("Prepare personas: " . mysqli_error($conexion));
        $dniToStore = preg_replace('/\D/', '', $dni);
        mysqli_stmt_bind_param($stmtP, 'sssss', $ape, $nom, $dniToStore, $fn, $sex);
        mysqli_stmt_execute($stmtP);
        $persona_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmtP);
      }

      // DOMICILIOS
      $stmtD = mysqli_prepare($conexion, "INSERT INTO domicilios (domicilios_calle, domicilios_descripcion, domicilios_latitud, domicilios_longitud, domicilios_predeterminado, personas_id) VALUES (?, ?, ?, ?, ?, ?)");
      if (!$stmtD) throw new Exception("Prepare domicilios: " . mysqli_error($conexion));
      foreach ($domicilios_calle as $i => $calle) {
        $c = (string)$calle;
        $d = (string)($domicilios_descripcion[$i] ?? '');
        $laRaw = $domicilios_lat[$i] ?? '';
        $lnRaw = $domicilios_lng[$i] ?? '';
        $pr = ($i === (int)$domicilios_pred) ? 1 : 0;

        // CONVERTIR LATITUD Y LONGITUD EN STRING
        $laValStr = ($laRaw === '' || $laRaw === null) ? null : str_replace(',', '.', (string)$laRaw);
        $lnValStr = ($lnRaw === '' || $lnRaw === null) ? null : str_replace(',', '.', (string)$lnRaw);
        $prVal = (int)$pr;
        $personaInt = (int)$persona_id;

        mysqli_stmt_bind_param($stmtD, 'ssssii', $c, $d, $laValStr, $lnValStr, $prVal, $personaInt);
        mysqli_stmt_execute($stmtD);
      }
      mysqli_stmt_close($stmtD);

      // TELEFONOS
      $stmtT = mysqli_prepare($conexion, "INSERT INTO telefonos (telefonos_numero, telefonos_descripcion, telefonos_predeterminado, personas_id) VALUES (?, ?, ?, ?)");
      if (!$stmtT) throw new Exception("Prepare telefonos: " . mysqli_error($conexion));
      foreach ($telefonos_numero as $i => $num) {
        $n = (string)$num;
        $d = (string)($telefonos_descripcion[$i] ?? '');
        $pr = ($i === (int)$telefonos_pred) ? 1 : 0;
        $personaInt = (int)$persona_id;
        mysqli_stmt_bind_param($stmtT, 'ssii', $n, $d, $pr, $personaInt);
        mysqli_stmt_execute($stmtT);
      }
      mysqli_stmt_close($stmtT);

      // INSTITUCIONAL, AGREGAMOS SEGUN TIPO
      $stmtDelInst = mysqli_prepare($conexion, "DELETE FROM institucional WHERE personas_id = ?");
      if (!$stmtDelInst) throw new Exception("Prepare institucional delete: " . mysqli_error($conexion));
      mysqli_stmt_bind_param($stmtDelInst, 'i', $persona_id);
      mysqli_stmt_execute($stmtDelInst);
      mysqli_stmt_close($stmtDelInst);

      // TIPO DIRECTOR
      if ($inst_tipo === 'Director') {
        $stmtI = mysqli_prepare($conexion, "INSERT INTO institucional (institucional_tipo, escuelas_id, personas_id) VALUES ('Director', ?, ?)");
        if (!$stmtI) throw new Exception("Prepare institucional insert director: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmtI, 'ii', $inst_escuela, $persona_id);
        mysqli_stmt_execute($stmtI);
        mysqli_stmt_close($stmtI);

        // TIPO DOCENTE
      } elseif ($inst_tipo === 'Docente') {
        $stmtI = mysqli_prepare($conexion, "INSERT INTO institucional (institucional_tipo, escuelas_id, formaciones_profesionales_id, personas_id) VALUES ('Docente', ?, ?, ?)");
        if (!$stmtI) throw new Exception("Prepare institucional insert docente: " . mysqli_error($conexion));
        mysqli_stmt_bind_param($stmtI, 'iii', $inst_escuela, $inst_formacion, $persona_id);
        mysqli_stmt_execute($stmtI);
        mysqli_stmt_close($stmtI);
      }

      // USUARIO
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmtU = mysqli_prepare($conexion, "INSERT INTO usuarios (personas_id, usuarios_email, usuarios_clave, usuarios_eliminado, usuarios_rol) VALUES (?, ?, ?, 0, ?)");
      if (!$stmtU) throw new Exception("Prepare usuarios: " . mysqli_error($conexion));
      mysqli_stmt_bind_param($stmtU, 'isss', $persona_id, $email, $hash, $rol);
      mysqli_stmt_execute($stmtU);
      mysqli_stmt_close($stmtU);

      mysqli_commit($conexion);

      // SWEETALERT2
      $registered = true;
    }
  } catch (Exception $e) {
    if (isset($conexion)) {
      @mysqli_rollback($conexion);
    }
    $errors[] = 'Error al guardar: ' . $e->getMessage();
    $show_step = 2;
  }
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
<link rel="stylesheet" href="CSS/registrarse.css">

<!-- CUERPO PRINCIPAL -->

<body class="login-page">
  <main class="fp-page">
    <h1>Registrarse</h1>

    <!-- MOSTRAR ERRORES -->
    <?php if (!empty($errors)): ?>
      <div class="error">
        <?php foreach ($errors as $err): ?>
          <p><?= htmlspecialchars($err) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div id="reg_validation_message" style="display:none"></div>

    <!-- FORMULARIO -->
    <form id="regForm" method="post" novalidate>
      <input type="hidden" name="step" value="final">

      <!-- PASO 1 -->
      <section id="step1" style="display: <?= $show_step === 1 ? 'block' : 'none' ?>">

        <!-- DATOS PERSONALES -->
        <h3>Datos Personales</h3>

        <div class="row">
          <label>Apellido
            <input name="personas_apellido" id="personas_apellido" value="<?= htmlspecialchars($_POST['personas_apellido'] ?? '') ?>">
          </label>

          <label>Nombre
            <input name="personas_nombre" id="personas_nombre" value="<?= htmlspecialchars($_POST['personas_nombre'] ?? '') ?>">
          </label>
        </div>

        <div class="row">
          <label>DNI
            <input name="personas_dni" id="personas_dni" value="<?= htmlspecialchars($_POST['personas_dni'] ?? '') ?>">
          </label>

          <label>Fecha Nac.
            <input type="date" name="personas_fechnac" id="personas_fechnac" value="<?= htmlspecialchars($_POST['personas_fechnac'] ?? '') ?>">
          </label>

          <label>Sexo
            <select name="personas_sexo" id="personas_sexo">
              <option value="">--</option>
              <option value="Masculino" <?= (isset($_POST['personas_sexo']) && $_POST['personas_sexo'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
              <option value="Femenino" <?= (isset($_POST['personas_sexo']) && $_POST['personas_sexo'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
            </select>
          </label>
        </div>

        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <div id="doms" class="blocks">
          <?php
          $doms_post = $_POST['domicilios_calle'] ?? [];
          if (!empty($doms_post) && is_array($doms_post)):
            foreach ($doms_post as $i => $val):
              $c = htmlspecialchars($val);
              $desc = htmlspecialchars($_POST['domicilios_descripcion'][$i] ?? '');
              $lat = htmlspecialchars($_POST['domicilios_latitud'][$i] ?? '');
              $lng = htmlspecialchars($_POST['domicilios_longitud'][$i] ?? '');
              $pred = (isset($_POST['domicilios_predeterminado']) && (int)$_POST['domicilios_predeterminado'] === $i) ? 'checked' : '';
          ?>
              <div class="dom-block">
                <?php if ($i > 0): ?><button type="button" class="del-dom">❌</button><?php endif; ?>
                <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número" value="<?= $c ?>"></label>

                <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción" value="<?= $desc ?>"></label>

                <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="<?= $i ?>" <?= $pred ?>></label>

                <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección"></label>

                <!-- BOTON BUSCAR -->
                <button type="button" class="btn-search">Buscar</button>
                <div class="map" style="height:150px;"></div>
                <input type="hidden" name="domicilios_latitud[]" value="<?= $lat ?>">
                <input type="hidden" name="domicilios_longitud[]" value="<?= $lng ?>">
              </div>
            <?php
            endforeach;
          else:
            ?>
            <div class="dom-block">
              <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número"></label>
              <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción"></label>
              <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="0" checked></label>
              <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección"></label>
              <button type="button" class="btn-search">Buscar</button>
              <div class="map" style="height:150px;"></div>
              <input type="hidden" name="domicilios_latitud[]">
              <input type="hidden" name="domicilios_longitud[]">
            </div>
          <?php endif; ?>
        </div>

        <!-- BOTON AGREGAR OTRO DOMICILIO -->
        <button type="button" id="addDom" class="pill">Agregar otro Domicilio</button>

        <!-- TELEFONO -->
        <h3>Teléfonos</h3>
        <div id="tels" class="blocks">
          <?php
          $tels_post = $_POST['telefonos_numero'] ?? [];
          if (!empty($tels_post) && is_array($tels_post)):
            foreach ($tels_post as $i => $val):
              $num = htmlspecialchars($val);
              $desc = htmlspecialchars($_POST['telefonos_descripcion'][$i] ?? '');
              $pred = (isset($_POST['telefonos_predeterminado']) && (int)$_POST['telefonos_predeterminado'] === $i) ? 'checked' : '';
          ?>
              <div class="tel-block">
                <?php if ($i > 0): ?><button type="button" class="del-tel">❌</button><?php endif; ?>
                <label>Número<br><input name="telefonos_numero[]" placeholder="Número" value="<?= $num ?>"></label>

                <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción" value="<?= $desc ?>"></label>

                <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="<?= $i ?>" <?= $pred ?>></label>
              </div>
            <?php
            endforeach;
          else:
            ?>
            <div class="tel-block">
              <label>Número<br><input name="telefonos_numero[]" placeholder="Número"></label>
              <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción"></label>
              <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="0" checked></label>
            </div>
          <?php endif; ?>
        </div>

        <!-- AGREGAR OTRO TELEFONO -->
        <button type="button" id="addTel" class="pill">Agregar otro Telefono</button>

        <!-- INSTITUCIONAL -->
        <h3>Institucional</h3>
        <div class="row">
          <label>Tipo de Persona
            <select name="inst_tipo" id="inst_tipo">
              <option value="">--</option>
              <option value="Director" <?= (isset($_POST['inst_tipo']) && $_POST['inst_tipo'] == 'Director') ? 'selected' : '' ?>>Director</option>
              <option value="Docente" <?= (isset($_POST['inst_tipo']) && $_POST['inst_tipo'] == 'Docente') ? 'selected' : '' ?>>Docente</option>
            </select>
          </label>

          <label id="label_escuela">Escuela
            <select name="inst_escuela" id="inst_escuela">
              <option value="">--</option>
              <?php mysqli_data_seek($escuelas, 0);
              while ($e = mysqli_fetch_assoc($escuelas)): ?>
                <option value="<?= $e['escuelas_id'] ?>" <?= (isset($_POST['inst_escuela']) && $_POST['inst_escuela'] == $e['escuelas_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['escuelas_nombre']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </label>

          <label id="label_formacion">Formación Profesional
            <select name="inst_formacion" id="inst_formacion">
              <option value="">--</option>
              <?php mysqli_data_seek($formaciones, 0);
              while ($f = mysqli_fetch_assoc($formaciones)): ?>
                <option value="<?= $f['formaciones_profesionales_id'] ?>" <?= (isset($_POST['inst_formacion']) && $_POST['inst_formacion'] == $f['formaciones_profesionales_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($f['formaciones_profesionales_nombre']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </label>
        </div>

        <!-- BOTON SIGUIENTE -->
        <div class="actions">
          <button type="button" id="toStep2" class="pill primary">Siguiente &rarr;</button>
        </div>
      </section>

      <!-- PASO 2 -->
      <section id="step2" style="display: <?= $show_step === 2 ? 'block' : 'none' ?>">

        <!-- DATOS DE USUARIO -->
        <h3>Datos de Usuario</h3>

        <div class="row">
          <label>Rol
            <select name="usuarios_rol">
              <option value="DIRECTOR">DIRECTOR</option>
              <option value="DOCENTE">DOCENTE</option>
            </select>
          </label>

          <label>Email
            <input type="email" name="usuarios_email" id="usuarios_email" value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
          </label>
        </div>

        <div class="row">
          <label>Contraseña
            <input type="password" name="usuarios_clave" id="usuarios_clave">
            <i class="toggle-password" toggle="#usuarios_clave" title="Mostrar/Ocultar"></i>
          </label>

          <label>Confirmar Contraseña
            <input type="password" name="usuarios_clave_conf" id="usuarios_clave_conf">
            <i class="toggle-password" toggle="#usuarios_clave_conf" title="Mostrar/Ocultar"></i>
          </label>
        </div>

        <div class="actions">
          <button type="button" id="backToStep1" class="pill">Volver</button>
          <button type="submit" id="btnRegister" class="pill primary">Registrarse</button>
        </div>
      </section>
    </form>

  </main>

  <!-- FOOTER -->
  <?php include('footer.php'); ?>

  <!-- ENDPOINTS ABSOLUTOS QUE USA JS -->
  <?php
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $scriptPath = $_SERVER['SCRIPT_NAME'];
  $geocodeEndpoint = $scheme . '://' . $host . $scriptPath;
  ?>
  <script>
    // VARIABLES QUE JS UTILIZARA
    window.GEOCODE_ENDPOINT = "<?= htmlspecialchars($geocodeEndpoint, ENT_QUOTES) ?>";
    console.log('GEOCODE_ENDPOINT (desde PHP):', window.GEOCODE_ENDPOINT);
  </script>

  <?php if (!empty($registered)): ?>
    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: 'success',
          title: 'Registro Exitoso',
          text: 'Tu usuario se creó correctamente. Serás redirigido al inicio de sesión.',
          confirmButtonText: 'Ir a Logeo',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            setTimeout(() => {
              window.location.href = 'logeo.php';
            }, 3500);
          }
        }).then(() => {
          window.location.href = 'logeo.php';
        });
      });
    </script>
  <?php endif; ?>

  <!-- SCRITPS -->
  <script src="JS/validaciones_registrarse.js" defer></script>
  <script src="JS/registrarse.js" defer></script>

</body>

</html>

<!-- ENVIAMOS BUFFER AL CLIENTE -->
<?php ob_end_flush(); ?>