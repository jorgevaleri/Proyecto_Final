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

// PERMITIR SOLO USUARIOS CON ROL
require_role_view(['ADMIN', 'DIRECTOR', 'DOCENTE']);
$role = current_role();

// INICIAR BUFFER DE SALIDA
ob_start();

// SECCIONES DISPONEBLES Y MAPEO
$allowed = ['todos', 'directores', 'docentes', 'alumnos'];
$tipo    = $_GET['tipo'] ?? 'todos';
if (!in_array($tipo, $allowed)) $tipo = 'todos';

$map = [
  'todos'      => ['titulo' => 'Todos',      'sql' => "i.personas_id IS NOT NULL"],
  'directores' => ['titulo' => 'Directores', 'sql' => "i.institucional_tipo='Director'"],
  'docentes'   => ['titulo' => 'Docentes',   'sql' => "i.institucional_tipo='Docente'"],
  'alumnos'    => ['titulo' => 'Alumnos',    'sql' => "i.institucional_tipo='Alumno'"]
];
$title = $map[$tipo]['titulo'];
$where = $map[$tipo]['sql'];

// SI EL USUARIO ES DOCENTE
if (is_docente()) {
  $map = [
    'alumnos' => ['titulo' => 'Alumnos', 'sql' => "i.institucional_tipo='Alumno'"]
  ];
  $tipo = 'alumnos';
  $title = $map[$tipo]['titulo'];
}

// SI EL USUARIO ES DIRECTOR
if (is_director()) {
  $map = [
    'docentes' => ['titulo' => 'Docentes', 'sql' => "i.institucional_tipo='Docente'"],
    'alumnos'  => ['titulo' => 'Alumnos',  'sql' => "i.institucional_tipo='Alumno'"]
  ];
  if (!isset($map[$tipo])) {
    $tipo = 'docentes';
    $title = $map[$tipo]['titulo'];
  }
}

// PARA DIRECTOR OBTENER ESCUELA DE REGISTRO
$director_escuela_id = 0;
if (is_director()) {
  $user_id = (int)($_SESSION['user_id'] ?? 0);
  $personas_id = (int)($_SESSION['personas_id'] ?? 0);

  if (!$personas_id && $user_id) {
    $q = mysqli_query($conexion, "SELECT personas_id FROM usuarios WHERE usuarios_id = {$user_id} LIMIT 1");
    if ($q && mysqli_num_rows($q)) $personas_id = (int)mysqli_fetch_assoc($q)['personas_id'];
  }

  if ($personas_id) {
    $q2 = mysqli_query($conexion, "SELECT escuelas_id FROM institucional WHERE personas_id = {$personas_id} LIMIT 1");
    if ($q2 && mysqli_num_rows($q2)) $director_escuela_id = (int)mysqli_fetch_assoc($q2)['escuelas_id'];
  }
}

// ACCION E ID
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error  = '';

// INICIALIZAR VARIABLES
$p = null;
$doms = [];
$tels = [];
$inst = null;

// ELIMINAR, SOLO ADMINISTRADOR
if ($action === 'delete' && $id) {
  require_role_action(['ADMIN']);

  // MOSTRAR CONFIRMACION
  $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));
  $doms_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id");
  $tels_q = mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id");
  $doms = $doms_q ? mysqli_fetch_all($doms_q, MYSQLI_ASSOC) : [];
  $tels = $tels_q ? mysqli_fetch_all($tels_q, MYSQLI_ASSOC) : [];

  // MARCAMOS COMO ELIMINADO
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
      mysqli_query($conexion, "UPDATE personas SET personas_eliminado=1 WHERE personas_id=$id")
        or die(mysqli_error($conexion));
      header("Location: personas.php?tipo=" . urlencode($tipo) . "&deleted=1");
      exit;
    } else {
      header("Location: personas.php?tipo=" . urlencode($tipo));
      exit;
    }
  }
}

// RESTAURAR, SOLO ADMINISTRADOR
if ($action === 'restore' && $id) {
  require_role_action(['ADMIN']);
  mysqli_query($conexion, "UPDATE personas SET personas_eliminado=0 WHERE personas_id=$id");
  header("Location: personas.php?tipo=" . urlencode($tipo) . "&restored=1");
  exit;
}

// VER, DETALLE DE PERSONA
if ($action === 'view' && $id) {

  // DATOS PERSONALES
  $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));

  // DOMICILIOS
  $doms_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id");
  $doms = $doms_q ? mysqli_fetch_all($doms_q, MYSQLI_ASSOC) : [];

  // TELEFONOS
  $tels_q = mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id");
  $tels = $tels_q ? mysqli_fetch_all($tels_q, MYSQLI_ASSOC) : [];

  // INSTITUCIONAL
  $inst = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM institucional WHERE personas_id=$id LIMIT 1"));

  // SI NO HAY PREDETERMINADO SE MARCA EL PRIMERO
  if (is_array($doms) && count($doms) > 0) {
    $hasPred = false;
    foreach ($doms as $dd) {
      if (!empty($dd['domicilios_predeterminado'])) {
        $hasPred = true;
        break;
      }
    }
    if (!$hasPred) $doms[0]['domicilios_predeterminado'] = 1;
  }

  if (is_array($tels) && count($tels) > 0) {
    $hasPred = false;
    foreach ($tels as $tt) {
      if (!empty($tt['telefonos_predeterminado'])) {
        $hasPred = true;
        break;
      }
    }
    if (!$hasPred) $tels[0]['telefonos_predeterminado'] = 1;
  }
}

// LISTA / ELIMINADOS
if ($action === 'list' || $action === 'deleted') {
  $sql = "
    SELECT
      p.personas_id,
      p.personas_apellido,
      p.personas_nombre,
      p.personas_dni,
      i.institucional_tipo
    FROM personas p
    LEFT JOIN institucional i
      ON i.personas_id = p.personas_id
    WHERE p.personas_eliminado = " . ($action === 'list' ? '0' : '1') . "
  ";

  if ($tipo !== 'todos') {
    $mapTipo = [
      'directores' => 'Director',
      'docentes'   => 'Docente',
      'alumnos'    => 'Alumno'
    ];
    $f = $mapTipo[$tipo];
    $sql .= " AND i.institucional_tipo = '" . mysqli_real_escape_string($conexion, $f) . "'";
  }

  // SI ES DIRECTOR, MOSTRAMOS DOCENTES PERTENECIENTES A LA ESCUELA
  if (is_director() && $tipo === 'docentes' && !empty($director_escuela_id)) {
    $sql .= " AND i.escuelas_id = " . (int)$director_escuela_id;
  }

  // ORDENAMOS POR ID
  $sql .= " ORDER BY p.personas_id";

  $res = mysqli_query($conexion, $sql) or die(mysqli_error($conexion));
}

// AGREGAR / EDITAR, SOLO ADMINISTRADOR Y DOCENTE
if (in_array($action, ['add', 'edit'])) {
  require_role_action(['ADMIN', 'DOCENTE']);

  // CARGAMOS DATOS
  $escuelas   = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado=0 ORDER BY escuelas_nombre");

  $formaciones = mysqli_query($conexion, "SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_eliminado=0 ORDER BY formaciones_profesionales_nombre");

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ape = mysqli_real_escape_string($conexion, $_POST['personas_apellido'] ?? '');
    $nom = mysqli_real_escape_string($conexion, $_POST['personas_nombre'] ?? '');
    $dni = mysqli_real_escape_string($conexion, $_POST['personas_dni'] ?? '');
    $fn  = mysqli_real_escape_string($conexion, $_POST['personas_fechnac'] ?? '');
    $sex = mysqli_real_escape_string($conexion, $_POST['personas_sexo'] ?? '');

    // INSERTAR PERSONA
    if ($action === 'add') {
      mysqli_query(
        $conexion,
        "INSERT INTO personas (personas_apellido,personas_nombre,personas_dni,personas_fechnac,personas_sexo)
         VALUES ('$ape','$nom','$dni','$fn','$sex')"
      ) or die(mysqli_error($conexion));
      $persona_id = mysqli_insert_id($conexion);

      // ACTUALIZAR PERSONA
    } else {
      mysqli_query(
        $conexion,
        "UPDATE personas SET
           personas_apellido='$ape',
           personas_nombre='$nom',
           personas_dni='$dni',
           personas_fechnac='$fn',
           personas_sexo='$sex'
         WHERE personas_id=$id"
      ) or die(mysqli_error($conexion));
      $persona_id = $id;
      mysqli_query($conexion, "DELETE FROM domicilios WHERE personas_id=$id");
      mysqli_query($conexion, "DELETE FROM telefonos   WHERE personas_id=$id");
    }

    // DOMICILIOS
    $pd = $_POST['domicilios_predeterminado'] ?? 0;
    $doms_calle = $_POST['domicilios_calle'] ?? [];
    foreach ($doms_calle as $i => $calle) {
      $c  = mysqli_real_escape_string($conexion, $calle);
      $d  = mysqli_real_escape_string($conexion, $_POST['domicilios_descripcion'][$i] ?? '');
      $la = mysqli_real_escape_string($conexion, $_POST['domicilios_latitud'][$i] ?? '');
      $ln = mysqli_real_escape_string($conexion, $_POST['domicilios_longitud'][$i] ?? '');
      $pr = ($i == (int)$pd) ? 1 : 0;
      mysqli_query(
        $conexion,
        "INSERT INTO domicilios (domicilios_calle,domicilios_descripcion,domicilios_latitud,domicilios_longitud,domicilios_predeterminado,personas_id)
         VALUES ('$c','$d','$la','$ln',$pr,$persona_id)"
      ) or die(mysqli_error($conexion));
    }

    // TELEFONOS
    $pt = $_POST['telefonos_predeterminado'] ?? 0;
    $telefonos_numero = $_POST['telefonos_numero'] ?? [];
    foreach ($telefonos_numero as $i => $num) {
      $n  = mysqli_real_escape_string($conexion, $num);
      $d  = mysqli_real_escape_string($conexion, $_POST['telefonos_descripcion'][$i] ?? '');
      $pr = ($i == (int)$pt) ? 1 : 0;
      mysqli_query(
        $conexion,
        "INSERT INTO telefonos (telefonos_numero,telefonos_descripcion,telefonos_predeterminado,personas_id)
         VALUES ('$n','$d',$pr,$persona_id)"
      ) or die(mysqli_error($conexion));
    }

    // INSTITUCIONAL
    mysqli_query($conexion, "DELETE FROM institucional WHERE personas_id=$persona_id")
      or die(mysqli_error($conexion));

    $inst_tipo = mysqli_real_escape_string($conexion, $_POST['inst_tipo'] ?? '');

    if ($inst_tipo === 'Director') {
      $inst_esc = (int) ($_POST['inst_escuela'] ?? 0);
      $sqlInst = "
      INSERT INTO institucional 
        (institucional_tipo, escuelas_id, personas_id)
      VALUES
        ('$inst_tipo', $inst_esc, $persona_id)
    ";
    } elseif ($inst_tipo === 'Docente') {
      $inst_esc  = (int) ($_POST['inst_escuela'] ?? 0);
      $inst_form = (int) ($_POST['inst_formacion'] ?? 0);
      $sqlInst = "
      INSERT INTO institucional 
        (institucional_tipo, escuelas_id, formaciones_profesionales_id, personas_id)
      VALUES
        ('$inst_tipo', $inst_esc, $inst_form, $persona_id)
    ";
    } elseif ($inst_tipo === 'Alumno') {
      $sqlInst = "
      INSERT INTO institucional 
        (institucional_tipo, personas_id)
      VALUES
        ('$inst_tipo', $persona_id)
    ";
    } else {
      $sqlInst = false;
    }

    if ($sqlInst) mysqli_query($conexion, $sqlInst) or die(mysqli_error($conexion));

    $saved_mode = ($action === 'add') ? 'add' : 'edit';
    header("Location: personas.php?tipo=" . urlencode($tipo) . "&saved=1&mode=" . urlencode($saved_mode));
    exit;
  }

  // EDITAR, SOLO ADMINISTRADOR Y DOCENTE
  if ($action === 'edit' && $id) {
    require_role_action(['ADMIN', 'DOCENTE']);
    $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));
    $doms_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id");
    $tels_q = mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id");
    $doms = $doms_q ? mysqli_fetch_all($doms_q, MYSQLI_ASSOC) : [];
    $tels = $tels_q ? mysqli_fetch_all($tels_q, MYSQLI_ASSOC) : [];
    $inst = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM institucional WHERE personas_id=$id LIMIT 1"));

    if (is_array($doms) && count($doms) > 0) {
      $hasPred = false;
      foreach ($doms as $dd) {
        if (!empty($dd['domicilios_predeterminado'])) {
          $hasPred = true;
          break;
        }
      }
      if (!$hasPred) $doms[0]['domicilios_predeterminado'] = 1;
    }
    if (is_array($tels) && count($tels) > 0) {
      $hasPred = false;
      foreach ($tels as $tt) {
        if (!empty($tt['telefonos_predeterminado'])) {
          $hasPred = true;
          break;
        }
      }
      if (!$hasPred) $tels[0]['telefonos_predeterminado'] = 1;
    }
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
<link rel="stylesheet" href="CSS/estilo_app.css">

<!-- MENU LATERAL -->
<?php include('menu_lateral.php'); ?>

<body>
  <main class="fp-page">

    <?php
    if ($action === 'list'):
    ?>
      <h1 class="title">Personas</h1>

      <nav>
        <!-- DOCENTE, SOLO VE ALUMNOS -->
        <?php if (is_docente()): ?>
          <a href="personas.php?tipo=alumnos" class="<?= 'alumnos' === $tipo ? 'active' : '' ?>">
            <?= htmlspecialchars($map['alumnos']['titulo']) ?>
          </a>
        <?php else: ?>
          <!-- ADMINISTRADOR, MENU COMPLETO -->
          <?php foreach ($map as $key => $cfg): ?>
            <a href="personas.php?tipo=<?= $key ?>" class="<?= $key === $tipo ? 'active' : '' ?>">
              <?= htmlspecialchars($cfg['titulo']) ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>
    <?php else: ?>
      <?php
      // TITULOS DESCRIPTIVOS
      $heading = 'Personas';
      if ($action === 'add') $heading = 'Agregar Persona';
      if ($action === 'edit') $heading = isset($p) ? 'Editar Persona: ' . htmlspecialchars($p['personas_apellido'] . ', ' . $p['personas_nombre']) : 'Editar Persona';
      if ($action === 'view') $heading = isset($p) ? 'Ver Persona: ' . htmlspecialchars($p['personas_apellido'] . ', ' . $p['personas_nombre']) : 'Ver Persona';
      if ($action === 'delete') $heading = isset($p) ? 'Eliminar Persona: ' . htmlspecialchars($p['personas_apellido'] . ', ' . $p['personas_nombre']) : 'Eliminar Persona';
      if ($action === 'deleted') $heading = 'Personas Eliminadas';
      ?>
      <h1 class="title"><?= $heading ?></h1>
    <?php endif; ?>

    <!-- VER -->
    <?php if ($action === 'view' && $id): ?>
      <div class="view-panel">

        <!-- DATOS PERSONALES -->
        <h3>Datos Personales</h3>
        <div class="row">
          <label>Apellido
            <input value="<?= htmlspecialchars($p['personas_apellido'] ?? '') ?>" disabled>
          </label>

          <label>Nombre
            <input value="<?= htmlspecialchars($p['personas_nombre'] ?? '') ?>" disabled>
          </label>
        </div>

        <div class="row">
          <label>DNI
            <input value="<?= htmlspecialchars($p['personas_dni'] ?? '') ?>" disabled>
          </label>

          <label>Fecha Nac.
            <input type="date" value="<?= htmlspecialchars($p['personas_fechnac'] ?? '') ?>" disabled>
          </label>

          <label>Sexo
            <input value="<?= htmlspecialchars($p['personas_sexo'] ?? '') ?>" disabled>
          </label>
        </div>

        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <div class="blocks">
          <?php if (!empty($doms) && is_array($doms)): ?>
            <?php foreach ($doms as $d): ?>
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
            <?php endforeach; ?>
          <?php else: ?>
            <p>No hay domicilios registrados.</p>
          <?php endif; ?>
        </div>

        <!-- TELEFONOS -->
        <h3>Teléfonos</h3>
        <div class="blocks">
          <?php if (!empty($tels) && is_array($tels)): ?>
            <?php foreach ($tels as $t): ?>
              <div class="tel-block">
                <label>Número<br><input value="<?= htmlspecialchars($t['telefonos_numero'] ?? '') ?>" disabled></label>
                <label>Descripción<br><input value="<?= htmlspecialchars($t['telefonos_descripcion'] ?? '') ?>" disabled></label>
                <label>Predeterminado<br><input value="<?= (!empty($t['telefonos_predeterminado']) ? 'Sí' : 'No') ?>" disabled></label>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No hay teléfonos registrados.</p>
          <?php endif; ?>
        </div>

        <!-- INSTITUCIONAL -->
        <h3>Institucional</h3>
        <?php if (!empty($inst)): ?>
          <div class="row">
            <label>Tipo de Persona<br><input value="<?= htmlspecialchars($inst['institucional_tipo']) ?>" disabled></label>
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

        <p style="margin-top:12px;">
          <a href="personas.php?tipo=<?= $tipo ?>" class="pill">Volver al listado</a>
        </p>
      </div>

      <!-- EDITAR / AGREGAR -->
    <?php elseif ($action === 'edit' || $action === 'add'): ?>
      <form id="personForm" method="post" novalidate>

        <!-- DATOS PERSONALES -->
        <h3>Datos Personales</h3>

        <div class="row">
          <label>Apellido
            <input name="personas_apellido" id="personas_apellido" value="<?= htmlspecialchars($p['personas_apellido'] ?? '') ?>">
          </label>

          <label>Nombre
            <input name="personas_nombre" id="personas_nombre" value="<?= htmlspecialchars($p['personas_nombre'] ?? '') ?>">
          </label>
        </div>

        <div class="row">
          <label>DNI
            <input name="personas_dni" id="personas_dni" value="<?= htmlspecialchars($p['personas_dni'] ?? '') ?>">
          </label>

          <label>Fecha Nac.
            <input type="date" name="personas_fechnac" id="personas_fechnac" value="<?= htmlspecialchars($p['personas_fechnac'] ?? '') ?>">
          </label>

          <label>Sexo
            <select name="personas_sexo" id="personas_sexo">
              <option value="">--</option>
              <option value="Masculino" <?= (isset($p['personas_sexo']) && $p['personas_sexo'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
              <option value="Femenino" <?= (isset($p['personas_sexo']) && $p['personas_sexo'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
            </select>
          </label>
        </div>

        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <div id="doms" class="blocks">
          <?php if (!empty($doms) && is_array($doms)): ?>
            <?php foreach ($doms as $i => $d): ?>
              <div class="dom-block">
                <?php if ($i > 0): ?><button type="button" class="del-dom">❌</button><?php endif; ?>
                <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número" value="<?= htmlspecialchars($d['domicilios_calle'] ?? '') ?>"></label>
                <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción" value="<?= htmlspecialchars($d['domicilios_descripcion'] ?? '') ?>"></label>
                <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="<?= $i ?>" <?= (!empty($d['domicilios_predeterminado']) ? 'checked' : '') ?>></label>
                <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección"></label>
                <button type="button" class="btn-search">Buscar</button>
                <div class="map" style="height:150px;"></div>
                <input type="hidden" name="domicilios_latitud[]" value="<?= htmlspecialchars($d['domicilios_latitud'] ?? '') ?>">
                <input type="hidden" name="domicilios_longitud[]" value="<?= htmlspecialchars($d['domicilios_longitud'] ?? '') ?>">
              </div>
            <?php endforeach; ?>
          <?php else: ?>
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

        <button type="button" id="addDom" class="pill">Agregar otro Domicilio</button>

        <!-- TELÉFONOS -->
        <h3>Teléfonos</h3>
        <div id="tels" class="blocks">
          <?php if (!empty($tels) && is_array($tels)): ?>
            <?php foreach ($tels as $i => $t): ?>
              <div class="tel-block">
                <?php if ($i > 0): ?><button type="button" class="del-tel">❌</button><?php endif; ?>
                <label>Número<br><input name="telefonos_numero[]" placeholder="Número" value="<?= htmlspecialchars($t['telefonos_numero'] ?? '') ?>"></label>
                <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción" value="<?= htmlspecialchars($t['telefonos_descripcion'] ?? '') ?>"></label>
                <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="<?= $i ?>" <?= (!empty($t['telefonos_predeterminado']) ? 'checked' : '') ?>></label>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="tel-block">
              <label>Número<br><input name="telefonos_numero[]" placeholder="Número"></label>
              <label>Descripción<br><input name="telefonos_descripcion[]" placeholder="Descripción"></label>
              <label>Predeterminado <input type="radio" name="telefonos_predeterminado" value="0" checked></label>
            </div>
          <?php endif; ?>
        </div>

        <button type="button" id="addTel" class="pill">Agregar otro Teléfono</button>

        <!-- INSTITUCIONAL -->
        <h3>Institucional</h3>
        <?php if (is_docente()): ?>
          <input type="hidden" name="inst_tipo" value="Alumno">
          <p><strong>Tipo de Persona:</strong> Alumno</p>
        <?php else: ?>
          <div class="row">
            <label>Tipo de Persona
              <select name="inst_tipo" id="inst_tipo">
                <option value="">--</option>
                <option value="Director" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Director') ? 'selected' : '' ?>>Director</option>
                <option value="Docente" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Docente') ? 'selected' : '' ?>>Docente</option>
                <option value="Alumno" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Alumno') ? 'selected' : '' ?>>Alumno</option>
              </select>
            </label>

            <label id="label_escuela">Escuela
              <select name="inst_escuela" id="inst_escuela">
                <option value="">--</option>
                <?php if (isset($escuelas)) {
                  mysqli_data_seek($escuelas, 0);
                  while ($e = mysqli_fetch_assoc($escuelas)): ?>
                    <option value="<?= $e['escuelas_id'] ?>" <?= (isset($inst['escuelas_id']) && $inst['escuelas_id'] == $e['escuelas_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($e['escuelas_nombre']) ?>
                    </option>
                <?php endwhile;
                } ?>
              </select>
            </label>

            <label id="label_formacion">Formación Profesional
              <select name="inst_formacion" id="inst_formacion">
                <option value="">--</option>
                <?php if (isset($formaciones)) {
                  mysqli_data_seek($formaciones, 0);
                  while ($f = mysqli_fetch_assoc($formaciones)): ?>
                    <option value="<?= $f['formaciones_profesionales_id'] ?>" <?= (isset($inst['formaciones_profesionales_id']) && $inst['formaciones_profesionales_id'] == $f['formaciones_profesionales_id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($f['formaciones_profesionales_nombre']) ?>
                    </option>
                <?php endwhile;
                } ?>
              </select>
            </label>
          </div>
        <?php endif; ?>

        <div class="actions">
          <button type="submit" class="pill primary"><?= $action === 'edit' ? 'Guardar cambios' : 'Guardar' ?></button>
          <a href="personas.php?tipo=<?= $tipo ?>" class="pill">Cancelar</a>
        </div>
      </form>

    <?php elseif ($action === 'delete' && $id): ?>

      <!-- ELIMINAR -->
      <h2>Confirmar Eliminación</h2>
      <p>¿Deseas eliminar esta persona y sus datos? Verifica la información a continuación antes de confirmar.</p>

      <form method="post" style="margin-top:12px;" novalidate>
        <div class="delete-actions" style="margin-bottom:16px;">
          <button type="submit" name="confirm" value="yes" class="pill primary">Sí, eliminar</button>
          <button type="submit" name="confirm" value="no" class="pill">Cancelar</button>
        </div>
      </form>

      <hr>
      <div class="view-panel">
        <h3>Datos Personales</h3>

        <div class="row">
          <label>Apellido
            <input value="<?= htmlspecialchars($p['personas_apellido'] ?? '') ?>" disabled>
          </label>

          <label>Nombre
            <input value="<?= htmlspecialchars($p['personas_nombre'] ?? '') ?>" disabled>
          </label>
        </div>

        <div class="row">
          <label>DNI
            <input value="<?= htmlspecialchars($p['personas_dni'] ?? '') ?>" disabled>
          </label>

          <label>Fecha Nac.
            <input type="date" value="<?= htmlspecialchars($p['personas_fechnac'] ?? '') ?>" disabled>
          </label>

          <label>Sexo
            <input value="<?= htmlspecialchars($p['personas_sexo'] ?? '') ?>" disabled>
          </label>
        </div>

        <h3>Domicilios</h3>
        <div class="blocks">
          <?php if (!empty($doms) && is_array($doms)): ?>
            <?php foreach ($doms as $d): ?>
              <div class="dom-block">
                <label>Calle y número<br><input value="<?= htmlspecialchars($d['domicilios_calle'] ?? '') ?>" disabled></label>
                <label>Descripción<br><input value="<?= htmlspecialchars($d['domicilios_descripcion'] ?? '') ?>" disabled></label>
                <label>Predeterminado<br><input value="<?= (!empty($d['domicilios_predeterminado']) ? 'Sí' : 'No') ?>" disabled></label>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No hay domicilios registrados.</p>
          <?php endif; ?>
        </div>

        <h3>Teléfonos</h3>
        <div class="blocks">
          <?php if (!empty($tels) && is_array($tels)): ?>
            <?php foreach ($tels as $t): ?>
              <div class="tel-block">
                <label>Número<br><input value="<?= htmlspecialchars($t['telefonos_numero'] ?? '') ?>" disabled></label>
                <label>Descripción<br><input value="<?= htmlspecialchars($t['telefonos_descripcion'] ?? '') ?>" disabled></label>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>No hay teléfonos registrados.</p>
          <?php endif; ?>
        </div>

        <h3>Institucional</h3>
        <?php if (!empty($inst)): ?>
          <div class="row">
            <label>Tipo de Persona<br><input value="<?= htmlspecialchars($inst['institucional_tipo']) ?>" disabled></label>
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

      </div>

    <?php else: ?>

      <!-- LISTADO -->
      <ul class="botones">
        <?php if ($action === 'list' && (is_admin() || is_docente())):
        ?>
          <li class="boton-agregar"><a href="personas.php?action=add&tipo=<?= $tipo ?>"><i class="bi bi-plus-circle"></i> Agregar</a></li>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>

          <!-- MOSTRAR ELIMINADOS, SOLO ADMINISTRADOR -->
          <?php if (is_admin()):
          ?>
            <li class="boton-volver"><a href="personas.php?action=deleted&tipo=<?= $tipo ?>"><i class="bi bi-eye-slash"></i> Mostrar Eliminados</a></li>
          <?php endif; ?>
        <?php else: ?>
          <li class="boton-volver"><a href="personas.php?tipo=<?= $tipo ?>"><i class="bi bi-arrow-left-circle"></i> Volver al Listado</a></li>
        <?php endif; ?>
      </ul>

      <div class="contenedor">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Apellido</th>
              <th>Nombre</th>
              <th>DNI</th>
              <th>Tipo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php $cnt = 0;
            while ($r = mysqli_fetch_assoc($res)): ?>
              <tr>
                <td><?= ++$cnt ?></td>
                <td class="text-left"><?= htmlspecialchars($r['personas_apellido']) ?></td>
                <td class="text-left"><?= htmlspecialchars($r['personas_nombre']) ?></td>
                <td><?= htmlspecialchars($r['personas_dni']) ?></td>
                <td><?= htmlspecialchars($r['institucional_tipo'] ?? '—') ?></td>
                <td>
                  <?php if ($action === 'list'): ?>
                    <a href="personas.php?action=view&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>" title="Ver Más" class="accion"><i class="bi bi-eye"></i></a>
                    <?php if (is_admin() || is_docente()): ?>
                      <a href="personas.php?action=edit&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>" title="Editar" class="accion"><i class="bi bi-pencil"></i></a>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                      <a href="personas.php?action=delete&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>" title="Eliminar" class="accion"><i class="bi bi-trash3" style="color:#c0392b;"></i></a>
                    <?php endif; ?>

                  <?php elseif ($action === 'deleted'): ?>
                    <?php if (is_admin()): ?>
                      <a href="personas.php?action=restore&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>" title="Restaurar" class="accion"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php else: ?>
                      -
                    <?php endif; ?>
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

  <?php
  // ENDPOINTS ABSOLUTOS QUE USA JS
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  $dir = dirname($_SERVER['SCRIPT_NAME']);
  if ($dir === '/' || $dir === '\\') $dir = '';
  $geocodeEndpoint = $scheme . '://' . $host . $dir . '/registrarse.php?action=geocode';
  $checkDniEndpoint = $scheme . '://' . $host . $dir . '/registrarse.php?action=check_dni';
  ?>

  <script>
    // VARIABLES QUE JS UTILIZARA
    window.GEOCODE_ENDPOINT = <?= json_encode($geocodeEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    window.CHECK_DNI_ENDPOINT = <?= json_encode($checkDniEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('form').forEach(function(f) {
        f.setAttribute('novalidate', 'novalidate');
      });
    });
  </script>

  <?php
  $saved = isset($_GET['saved']) ? (int)$_GET['saved'] : 0;
  $saved_mode = $_GET['mode'] ?? '';
  $deleted = isset($_GET['deleted']) ? (int)$_GET['deleted'] : 0;
  $restored = isset($_GET['restored']) ? (int)$_GET['restored'] : 0;
  if ($saved || $deleted || $restored):
  ?>

    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        <?php if ($saved):
          $msg = ($saved_mode === 'add') ? 'Persona agregada correctamente.' : 'Cambios guardados correctamente.';
        ?>
          Swal.fire({
            icon: 'success',
            title: 'Guardado',
            text: <?= json_encode($msg) ?>,
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'personas.php?tipo=<?= rawurlencode($tipo) ?>';
          });
        <?php elseif ($deleted): ?>
          Swal.fire({
            icon: 'success',
            title: 'Eliminado',
            text: 'La persona fue eliminada correctamente.',
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'personas.php?tipo=<?= rawurlencode($tipo) ?>';
          });
        <?php elseif ($restored): ?>
          Swal.fire({
            icon: 'success',
            title: 'Restaurado',
            text: 'La persona fue restaurada correctamente.',
            confirmButtonText: 'Aceptar',
            allowOutsideClick: false
          }).then(function() {
            window.location.href = 'personas.php?tipo=<?= rawurlencode($tipo) ?>';
          });
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <!-- SCRIPTS -->
  <script src="JS/personas.js" defer></script>
  <script src="JS/validaciones_personas.js" defer></script>
</body>

</html>

<!-- ENVIAMOS BUFFER AL CLIENTE -->
<?php ob_end_flush(); ?>