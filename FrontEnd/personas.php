<?php
session_start();

// Evitar cache después del logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Comprobar login
if (empty($_SESSION['user_id'])) {
  header('Location: index.php', true, 303);
  exit;
}

// Incluir helpers y conexion (require_once para evitar redeclare)
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/../BackEnd/conexion.php';

// Permitir sólo usuarios con rol (ADMIN, DIRECTOR, DOCENTE)
require_role_view(['ADMIN','DIRECTOR','DOCENTE']);
$role = current_role();

ob_start();
include('head.php');
include('header.php');
include('menu_lateral.php');

// 1) Secciones disponibles y mapeo
$allowed = ['todos','directores','docentes','alumnos'];
$tipo    = $_GET['tipo'] ?? 'todos';
if (!in_array($tipo, $allowed)) $tipo = 'todos';

$map = [
  'todos'      => ['titulo'=>'Todos',      'sql'=>"i.personas_id IS NOT NULL"],
  'directores' => ['titulo'=>'Directores', 'sql'=>"i.institucional_tipo='Director'"],
  'docentes'   => ['titulo'=>'Docentes',   'sql'=>"i.institucional_tipo='Docente'"],
  'alumnos'    => ['titulo'=>'Alumnos',    'sql'=>"i.institucional_tipo='Alumno'"]
];
$title = $map[$tipo]['titulo'];
$where = $map[$tipo]['sql'];

// --- Si el usuario es DOCENTE: limitar mapa y forzar 'alumnos' ----
if (is_docente()) {
    // dejamos solamente la vista "alumnos" y forzamos tipo
    $map = [
      'alumnos' => ['titulo' => 'Alumnos', 'sql' => "i.institucional_tipo='Alumno'"]
    ];
    $tipo = 'alumnos';
    $title = $map[$tipo]['titulo'];
}

// --- Si es DIRECTOR: mostrar solo Docentes y Alumnos en el submenu ----
if (is_director()) {
    $map = [
      'docentes' => ['titulo' => 'Docentes', 'sql' => "i.institucional_tipo='Docente'"],
      'alumnos'  => ['titulo' => 'Alumnos',  'sql' => "i.institucional_tipo='Alumno'"]
    ];
    // Si el tipo actual no está en el nuevo mapa, forzamos uno por defecto
    if (!isset($map[$tipo])) {
        $tipo = 'docentes';
        $title = $map[$tipo]['titulo'];
    }
}

// --- Para DIRECTOR: obtener la escuela donde está registrado (si es director) ---
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

// 2) Acción e ID
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error  = '';

// ----------------- ACCIONES: permisos de servidor -----------------

// DELETE: sólo ADMIN puede entrar aquí (tanto GET confirm como POST)
if ($action === 'delete' && $id) {
  require_role_action(['ADMIN']); // <-- SOLO ADMIN

  // Cargar datos para mostrar en confirmación
  $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));
  $doms = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id");
  $tels = mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id");

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
      mysqli_query($conexion, "UPDATE personas SET personas_eliminado=1 WHERE personas_id=$id")
        or die(mysqli_error($conexion));
    }
    header("Location: personas.php?tipo=$tipo");
    exit;
  }
}

// RESTORE: sólo ADMIN
if ($action === 'restore' && $id) {
  require_role_action(['ADMIN']); // <-- SOLO ADMIN
  mysqli_query($conexion, "UPDATE personas SET personas_eliminado=0 WHERE personas_id=$id");
  header("Location: personas.php?tipo=$tipo");
  exit;
}

// VIEW: detalle persona (ADMIN, DIRECTOR, DOCENTE) -> ya cubierto por require_role_view arriba
if ($action === 'view' && $id) {
  // Datos Personales
  $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));
  // Domicilios
  $doms = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id");
  // Telefonos
  $tels = mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id");
  // Datos Institucionales
  $inst = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM institucional WHERE personas_id=$id LIMIT 1"));
}

// LIST o DELETED
if ($action === 'list' || $action === 'deleted') {
  // Base de la consulta: LEFT JOIN para incluir personas sin registro institucional
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

  // Si no es 'todos', agregamos filtro por tipo institucional
  if ($tipo !== 'todos') {
    // Mapeo URL → valor real en la BD
    $mapTipo = [
      'directores' => 'Director',
      'docentes'   => 'Docente',
      'alumnos'    => 'Alumno'
    ];
    $f = $mapTipo[$tipo];
    $sql .= " AND i.institucional_tipo = '" . mysqli_real_escape_string($conexion, $f) . "'";
  }

  // Si es DIRECTOR y estamos mostrando 'docentes', limitamos por su escuela
  if (is_director() && $tipo === 'docentes' && !empty($director_escuela_id)) {
      $sql .= " AND i.escuelas_id = " . (int)$director_escuela_id;
  }

  // Ordenamos siempre por ID
  $sql .= " ORDER BY p.personas_id";

  // Ejecutamos
  $res = mysqli_query($conexion, $sql) or die(mysqli_error($conexion));
}

// ADD & EDIT: sólo ADMIN y DOCENTE pueden crear o editar
if (in_array($action, ['add', 'edit'])) {
  require_role_action(['ADMIN','DOCENTE']); // <-- ADMIN y DOCENTE pueden acceder aquí

  // Cargamos select institucional
  $escuelas   = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado=0 ORDER BY escuelas_nombre");
  $formaciones = mysqli_query($conexion, "SELECT formaciones_profesionales_id, formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_eliminado=0 ORDER BY formaciones_profesionales_nombre");

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos personales (sanitizar)
    $ape = mysqli_real_escape_string($conexion, $_POST['personas_apellido']);
    $nom = mysqli_real_escape_string($conexion, $_POST['personas_nombre']);
    $dni = mysqli_real_escape_string($conexion, $_POST['personas_dni']);
    $fn  = mysqli_real_escape_string($conexion, $_POST['personas_fechnac']);
    $sex = mysqli_real_escape_string($conexion, $_POST['personas_sexo']);

    if ($action === 'add') {
      mysqli_query(
        $conexion,
        "INSERT INTO personas (personas_apellido,personas_nombre,personas_dni,personas_fechnac,personas_sexo)
         VALUES ('$ape','$nom','$dni','$fn','$sex')"
      ) or die(mysqli_error($conexion));
      $persona_id = mysqli_insert_id($conexion);
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

    // Domicilios
    $pd = $_POST['domicilios_predeterminado'] ?? 0;
    foreach ($_POST['domicilios_calle'] as $i => $calle) {
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

    // Teléfonos
    $pt = $_POST['telefonos_predeterminado'] ?? 0;
    foreach ($_POST['telefonos_numero'] as $i => $num) {
      $n  = mysqli_real_escape_string($conexion, $num);
      $d  = mysqli_real_escape_string($conexion, $_POST['telefonos_descripcion'][$i] ?? '');
      $pr = ($i == (int)$pt) ? 1 : 0;
      mysqli_query(
        $conexion,
        "INSERT INTO telefonos (telefonos_numero,telefonos_descripcion,telefonos_predeterminado,personas_id)
         VALUES ('$n','$d',$pr,$persona_id)"
      ) or die(mysqli_error($conexion));
    }

    // --- Institucional ---
    mysqli_query($conexion, "DELETE FROM institucional WHERE personas_id=$persona_id")
      or die(mysqli_error($conexion));

    $inst_tipo = mysqli_real_escape_string($conexion, $_POST['inst_tipo']);

    if ($inst_tipo === 'Director') {
      $inst_esc = (int) $_POST['inst_escuela'];
      $sqlInst = "
      INSERT INTO institucional 
        (institucional_tipo, escuelas_id, personas_id)
      VALUES
        ('$inst_tipo', $inst_esc, $persona_id)
    ";
    } elseif ($inst_tipo === 'Docente') {
      $inst_esc  = (int) $_POST['inst_escuela'];
      $inst_form = (int) $_POST['inst_formacion'];
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

    header("Location: personas.php?tipo=$tipo");
    exit;
  }

  if ($action === 'edit' && $id) {
    // Sólo ADMIN y DOCENTE pueden abrir el formulario de edición
    require_role_action(['ADMIN','DOCENTE']);
    $p    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM personas WHERE personas_id=$id"));
    $doms = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id=$id"), MYSQLI_ASSOC);
    $tels = mysqli_fetch_all(mysqli_query($conexion, "SELECT * FROM telefonos   WHERE personas_id=$id"), MYSQLI_ASSOC);
    $inst = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM institucional WHERE personas_id=$id LIMIT 1"));
  }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Personas</title>

  <!-- ESTILOS -->
  <link rel="stylesheet" href="CSS/style_common.css">
  <link rel="stylesheet" href="CSS/style_app.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script defer src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

</head>

<body>
  <main class="fp-page">

    <h1>Personas</h1>
   <nav>
      <?php if (is_docente()): ?>
        <!-- DOCENTE: solo ver Alumnos -->
        <a href="personas.php?tipo=alumnos" <?= 'alumnos' === $tipo ? 'style="font-weight:bold"' : '' ?>>
          <?= htmlspecialchars($map['alumnos']['titulo']) ?>
        </a>
      <?php else: ?>
        <!-- ADMIN / otros: menú completo -->
        <?php foreach ($map as $key => $cfg): ?>
          <a href="personas.php?tipo=<?= $key ?>" <?= $key === $tipo ? 'style="font-weight:bold"' : '' ?>>
            <?= htmlspecialchars($cfg['titulo']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>

    <?php if ($action === 'view' && $id): ?>
      <!-- VIEW: permitido para ADMIN, DIRECTOR, DOCENTE -->
      <h2>Datos Personales</h2>
      <p><strong>Apellido:</strong> <?= htmlspecialchars($p['personas_apellido']) ?></p>
      <p><strong>Nombre:</strong> <?= htmlspecialchars($p['personas_nombre']) ?></p>
      <p><strong>DNI:</strong> <?= htmlspecialchars($p['personas_dni']) ?></p>
      <p><strong>Fecha Nac.:</strong> <?= htmlspecialchars($p['personas_fechnac']) ?></p>
      <p><strong>Sexo:</strong> <?= htmlspecialchars($p['personas_sexo']) ?></p>

      <h2>Domicilios</h2>
      <?php while ($d = mysqli_fetch_assoc($doms)): ?>
        <div>
          <p><strong>Dirección:</strong> <?= htmlspecialchars($d['domicilios_calle']) ?></p>
          <p><strong>Descripción:</strong> <?= htmlspecialchars($d['domicilios_descripcion']) ?></p>
          <p><strong>Predeterminado:</strong> <?= $d['domicilios_predeterminado'] ? 'Sí' : 'No' ?></p>
          <p><strong>Ubicación:</strong> <?= htmlspecialchars($d['domicilios_latitud']) . ", " . htmlspecialchars($d['domicilios_longitud']) ?></p>
          <?php if ($d['domicilios_latitud'] && $d['domicilios_longitud']): ?>
            <iframe width="100%" height="200" frameborder="0" style="border:0"
              src="https://maps.google.com/maps?q=<?= $d['domicilios_latitud'] ?>,<?= $d['domicilios_longitud'] ?>&z=15&output=embed"
              loading="lazy"></iframe>
          <?php endif; ?>
        </div>
      <?php endwhile; ?>

      <h2>Teléfonos</h2>
      <?php while ($t = mysqli_fetch_assoc($tels)): ?>
        <div>
          <p><strong>Teléfono:</strong> <?= htmlspecialchars($t['telefonos_numero']) ?></p>
          <p><strong>Descripción:</strong> <?= htmlspecialchars($t['telefonos_descripcion']) ?></p>
          <p><strong>Predeterminado:</strong> <?= $t['telefonos_predeterminado'] ? 'Sí' : 'No' ?></p>
        </div>
      <?php endwhile; ?>

      <h2>Institucional</h2>
      <?php if (!empty($inst)): ?>
        <p><strong>Tipo:</strong> <?= htmlspecialchars($inst['institucional_tipo']) ?></p>
        <p><strong>Escuela:</strong> <?php
                                      $esc = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT escuelas_nombre FROM escuelas WHERE escuelas_id=" . (int)$inst['escuelas_id']));
                                      echo htmlspecialchars($esc['escuelas_nombre']);
                                      ?></p>
        <?php if ($inst['formaciones_profesionales_id']): ?>
          <p><strong>Formación:</strong> <?php
                                          $fo = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_id=" . (int)$inst['formaciones_profesionales_id']));
                                          echo htmlspecialchars($fo['formaciones_profesionales_nombre']);
                                          ?></p><?php endif; ?>
      <?php else: ?>
        <p>No hay datos institucionales.</p>
      <?php endif; ?>

      <p><a href="personas.php?tipo=<?= $tipo ?>">Volver al listado</a></p>

    <?php elseif ($action === 'edit' || $action === 'add'): ?>
      <!-- ADD/EDIT: sólo ADMIN y DOCENTE pueden ver este formulario -->
      <h2><?= $action === 'edit' ? 'Editar' : 'Agregar' ?> Persona</h2>
      <form method="post">
        <label>Apellido:<br>
          <input name="personas_apellido" required value="<?= htmlspecialchars($p['personas_apellido'] ?? '') ?>">
        </label><br>
        <label>Nombre:<br>
          <input name="personas_nombre" required value="<?= htmlspecialchars($p['personas_nombre'] ?? '') ?>">
        </label><br>
        <label>DNI:<br>
          <input name="personas_dni" required value="<?= htmlspecialchars($p['personas_dni'] ?? '') ?>">
        </label><br>
        <label>Fecha Nac.:<br>
          <input type="date" name="personas_fechnac" required value="<?= htmlspecialchars($p['personas_fechnac'] ?? '') ?>">
        </label><br>
        <label>Sexo:<br>
          <select name="personas_sexo" required>
            <option value="">--</option>
            <option value="Masculino" <?= (isset($p['personas_sexo']) && $p['personas_sexo'] == 'Masculino') ? 'selected' : '' ?>>Masculino</option>
            <option value="Femenino" <?= (isset($p['personas_sexo']) && $p['personas_sexo'] == 'Femenino') ? 'selected' : '' ?>>Femenino</option>
          </select>
        </label><br>
        
        <!-- DOMICILIOS -->
        <h3>Domicilios</h3>
        <button type="button" id="addDom">Agregar domicilio</button>
        <div id="doms">
          <?php foreach (($doms ?? []) as $i => $d): ?>
            <div class="dom-block">
              <?php if ($i > 0): ?>
                <button type="button" class="del-dom">❌</button>
              <?php endif; ?>
              <label>
                Calle y número<br>
                <input name="domicilios_calle[]" placeholder="Calle y número" required
                  value="<?= htmlspecialchars($d['domicilios_calle']) ?>">
              </label>
              <label>
                Descripción<br>
                <input name="domicilios_descripcion[]" placeholder="Descripción"
                  value="<?= htmlspecialchars($d['domicilios_descripcion']) ?>">
              </label>
              <label>
                Predeterminado
                <input type="radio" name="domicilios_predeterminado" value="<?= $i ?>"
                  <?= $d['domicilios_predeterminado'] ? 'checked' : '' ?>>
              </label>
              <label>
                Buscar dirección<br>
                <input class="map-search" placeholder="Buscar dirección">
              </label>
              <button type="button" class="btn-search">Buscar</button>
              <div class="map" style="height:150px;"></div>
              <input type="hidden" name="domicilios_latitud[]" value="<?= htmlspecialchars($d['domicilios_latitud']) ?>">
              <input type="hidden" name="domicilios_longitud[]" value="<?= htmlspecialchars($d['domicilios_longitud']) ?>">
            </div>
          <?php endforeach; ?>

          <?php if (empty($doms)): ?>
            <div class="dom-block">
              <!-- no ❌ en el primero -->
              <label>
                Calle y número<br>
                <input name="domicilios_calle[]" placeholder="Calle y número" required>
              </label>
              <label>
                Descripción<br>
                <input name="domicilios_descripcion[]" placeholder="Descripción">
              </label>
              <label>
                Predeterminado
                <input type="radio" name="domicilios_predeterminado" value="0" checked>
              </label>
              <label>
                Buscar dirección<br>
                <input class="map-search" placeholder="Buscar dirección">
              </label>
              <button type="button" class="btn-search">Buscar</button>
              <div class="map" style="height:150px;"></div>
              <input type="hidden" name="domicilios_latitud[]">
              <input type="hidden" name="domicilios_longitud[]">
            </div>
          <?php endif; ?>
        </div>

        <!-- TELÉFONOS -->
        <h3>Teléfonos</h3>
        <button type="button" id="addTel">Agregar teléfono</button>
        <div id="tels">
          <?php foreach (($tels ?? []) as $i => $t): ?>
            <div class="tel-block">
              <?php if ($i > 0): ?>
                <button type="button" class="del-tel">❌</button>
              <?php endif; ?>
              <label>
                Número<br>
                <input name="telefonos_numero[]" placeholder="Número" required
                  value="<?= htmlspecialchars($t['telefonos_numero']) ?>">
              </label>
              <label>
                Descripción<br>
                <input name="telefonos_descripcion[]" placeholder="Descripción"
                  value="<?= htmlspecialchars($t['telefonos_descripcion']) ?>">
              </label>
              <label>
                Predeterminado
                <input type="radio" name="telefonos_predeterminado" value="<?= $i ?>"
                  <?= $t['telefonos_predeterminado'] ? 'checked' : '' ?>>
              </label>
            </div>
          <?php endforeach; ?>

          <?php if (empty($tels)): ?>
            <div class="tel-block">
              <!-- no ❌ en el primero -->
              <label>
                Número<br>
                <input name="telefonos_numero[]" placeholder="Número" required>
              </label>
              <label>
                Descripción<br>
                <input name="telefonos_descripcion[]" placeholder="Descripción">
              </label>
              <label>
                Predeterminado
                <input type="radio" name="telefonos_predeterminado" value="0" checked>
              </label>
            </div>
          <?php endif; ?>
        </div>

        <!-- Institucional -->
        <h3>Institucional</h3>

        <?php if (is_docente()): ?>
          <!-- Si es DOCENTE: no mostramos select, forzamos Alumno -->
          <input type="hidden" name="inst_tipo" value="Alumno">
          <p><strong>Tipo de Persona:</strong> Alumno</p>
        <?php else: ?>
          <label>
            Tipo de Persona:<br>
            <select name="inst_tipo" id="inst_tipo" required>
              <option value="">--</option>
              <option value="Director" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Director') ? 'selected' : '' ?>>Director</option>
              <option value="Docente" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Docente') ? 'selected' : '' ?>>Docente</option>
              <option value="Alumno" <?= (isset($inst['institucional_tipo']) && $inst['institucional_tipo'] == 'Alumno') ? 'selected' : '' ?>>Alumno</option>
            </select>
          </label><br>
        <?php endif; ?>

         <?php if (!is_docente()): ?>
        <label id="label_escuela">
          Escuela:<br>
          <select name="inst_escuela" id="inst_escuela">
            <option value="">--</option>
            <?php if (isset($escuelas)) { mysqli_data_seek($escuelas, 0); while ($e = mysqli_fetch_assoc($escuelas)): ?>
              <option value="<?= $e['escuelas_id'] ?>" <?= (isset($inst['escuelas_id']) && $inst['escuelas_id'] == $e['escuelas_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['escuelas_nombre']) ?>
              </option>
            <?php endwhile; } ?>
          </select>
        </label><br>

        <label id="label_formacion">
          Formación Profesional:<br>
          <select name="inst_formacion" id="inst_formacion">
            <option value="">--</option>
            <?php if (isset($formaciones)) { mysqli_data_seek($formaciones, 0); while ($f = mysqli_fetch_assoc($formaciones)): ?>
              <option value="<?= $f['formaciones_profesionales_id'] ?>" <?= (isset($inst['formaciones_profesionales_id']) && $inst['formaciones_profesionales_id'] == $f['formaciones_profesionales_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($f['formaciones_profesionales_nombre']) ?>
              </option>
            <?php endwhile; } ?>
          </select>
        </label><br>
        <?php endif; ?>

        <button type="submit"><?= $action === 'edit' ? 'Guardar cambios' : 'Guardar' ?></button>
        <a href="personas.php?tipo=<?= $tipo ?>">Cancelar</a>
      </form>

    <?php elseif ($action === 'delete' && $id): ?>
      <!-- DELETE: sólo ADMIN (ya verificado en servidor) -->
      <h2>Confirmar Eliminación</h2>
      <p>¿Deseas eliminar esta persona y sus datos?</p>
      <form method="post">
        <button type="submit" name="confirm" value="yes">Sí, eliminar</button>
        <button type="submit" name="confirm" value="no">Cancelar</button>
      </form>
      <hr>
      <!-- Mostrar datos como en VIEW -->
      <h3>Datos Personales</h3>
      <p><strong>Apellido:</strong> <?= htmlspecialchars($p['personas_apellido']) ?></p>
      <p><strong>Nombre:</strong> <?= htmlspecialchars($p['personas_nombre']) ?></p>
      <p><strong>DNI:</strong> <?= htmlspecialchars($p['personas_dni']) ?></p>
      <p><strong>Fecha Nac.:</strong> <?= htmlspecialchars($p['personas_fechnac']) ?></p>
      <p><strong>Sexo:</strong> <?= htmlspecialchars($p['personas_sexo']) ?></p>
      <h3>Domicilios</h3>
      <?php while ($d = mysqli_fetch_assoc($doms)): ?>
        <p><?= htmlspecialchars($d['domicilios_calle']) ?> (<?= htmlspecialchars($d['domicilios_descripcion']) ?>) <?= $d['domicilios_predeterminado'] ? '[Pred]' : '' ?></p>
      <?php endwhile; ?>
      <h3>Teléfonos</h3>
      <?php while ($t = mysqli_fetch_assoc($tels)): ?>
        <p><?= htmlspecialchars($t['telefonos_numero']) ?> (<?= htmlspecialchars($t['telefonos_descripcion']) ?>) <?= $t['telefonos_predeterminado'] ? '[Pred]' : '' ?></p>
      <?php endwhile; ?>

    <?php else: ?>


      <!-- LISTADO -->
        <p>
        <?php if (is_admin() || is_docente()): ?>
          <a href="personas.php?action=add&tipo=<?= $tipo ?>">Agregar Persona</a>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
          <?php if (is_admin()): // SOLO ADMIN VE "Mostrar Eliminados" ?>
            | <a href="personas.php?action=deleted&tipo=<?= $tipo ?>">Mostrar Eliminados</a>
          <?php endif; ?>
        <?php else: ?>
          | <a href="personas.php?tipo=<?= $tipo ?>">Volver a Activos</a>
        <?php endif; ?>
      </p>

      <table>
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
              <td><?= htmlspecialchars($r['personas_apellido']) ?></td>
              <td><?= htmlspecialchars($r['personas_nombre']) ?></td>
              <td><?= htmlspecialchars($r['personas_dni']) ?></td>
              <td><?= htmlspecialchars($r['institucional_tipo'] ?? '—') ?></td>
              <td>
                <?php if ($action === 'list'): ?>
                  <a href="personas.php?action=view&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>">Ver más</a>
                  <?php if (is_admin() || is_docente()): ?>
                    | <a href="personas.php?action=edit&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>">Editar</a>
                  <?php endif; ?>
                  <?php if (is_admin()): ?>
                    | <a href="personas.php?action=delete&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>">Eliminar</a>
                  <?php endif; ?>

                <?php elseif ($action === 'deleted'): ?>
                  <?php if (is_admin()): ?>
                    <a href="personas.php?action=restore&id=<?= $r['personas_id'] ?>&tipo=<?= $tipo ?>">Restaurar</a>
                  <?php else: ?>
                    <!-- Si no es admin, no mostramos acciones en eliminados -->
                    -
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </main>
  <?php include('footer.php'); ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Asegurar un solo radio predeterminado por grupo
      function fixRadios(groupName) {
        document.querySelectorAll(`input[name="${groupName}"]`).forEach(radio => {
          radio.addEventListener('change', () => {
            /* el browser desmarca los demás */
          });
        });
      }
      fixRadios('domicilios_predeterminado');
      fixRadios('telefonos_predeterminado');

      // === DOMICILIOS ===
      const doms = document.getElementById('doms'),
        baseDom = doms.querySelector('.dom-block'),
        btnDom = document.getElementById('addDom');

      function initDom(block, idx) {
        // Eliminar bloque secundario
        const delBtn = block.querySelector('.del-dom');
        if (delBtn) delBtn.addEventListener('click', () => block.remove());

        // Elementos de geocoding
        const mapEl = block.querySelector('.map'),
          search = block.querySelector('.map-search'),
          btnSearch = block.querySelector('.btn-search'),
          latInput = block.querySelector('input[name="domicilios_latitud[]"]'),
          lngInput = block.querySelector('input[name="domicilios_longitud[]"]');

        mapEl.innerHTML = '';
        const map = L.map(mapEl).setView([-28.4682, -65.7795], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '© OpenStreetMap'
        }).addTo(map);

        const marker = L.marker(map.getCenter(), {
            draggable: true
          })
          .addTo(map)
          .on('moveend', e => {
            const {
              lat,
              lng
            } = e.target.getLatLng();
            latInput.value = lat;
            lngInput.value = lng;
            console.log('Marcador movido a:', lat, lng);
          });

        marker.on('move', e => {
          console.log('Durante move:', e.latlng.lat, e.latlng.lng);
        });

        // Centrar si ya hay coords
        if (latInput.value && lngInput.value) {
          const la = parseFloat(latInput.value),
            lo = parseFloat(lngInput.value);
          marker.setLatLng([la, lo]);
          map.setView([la, lo], 15);
        }

        // Búsqueda con botón
        btnSearch.addEventListener('click', () => {
          const q = search.value.trim();
          if (!q) return alert('Ingresa una dirección');
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}`)
            .then(r => r.json()).then(data => {
              if (!data[0]) return alert('Dirección no encontrada');
              const la = parseFloat(data[0].lat),
                lo = parseFloat(data[0].lon);
              marker.setLatLng([la, lo]);
              map.setView([la, lo], 15);
              latInput.value = la;
              lngInput.value = lo;
              console.log('Coordenadas encontradas:', la, lo);
            })
            .catch(() => alert('Error al buscar en el mapa'));
        });
      }

      // Inicializar todos los bloques al cargar
      doms.querySelectorAll('.dom-block').forEach((blk, i) => initDom(blk, i));

      // Clonar domicilio
      btnDom.addEventListener('click', () => {
        const idx = doms.children.length;
        const clone = baseDom.cloneNode(true);

        // limpiar valores
        clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
        clone.querySelector('input[type="radio"]').value = idx;

        // si no existe aún, añadimos el botón de eliminar
        if (!clone.querySelector('.del-dom')) {
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'del-dom';
          del.textContent = '❌';
          clone.insertBefore(del, clone.firstChild);
        }

        doms.appendChild(clone);
        initDom(clone, idx);
      });



      // === TELÉFONOS ===
      const tels = document.getElementById('tels'),
        baseTel = tels.querySelector('.tel-block'),
        btnTel = document.getElementById('addTel');

      function initTel(block, idx) {
        const delBtn = block.querySelector('.del-tel');
        if (delBtn) delBtn.addEventListener('click', () => block.remove());
        // los radios de teléfonos mantienen por sí mismos la exclusividad
      }

      // Inicializar existentes
      tels.querySelectorAll('.tel-block').forEach((blk, i) => initTel(blk, i));

      // Clonar teléfono
      btnTel.addEventListener('click', () => {
        const idx = tels.children.length;
        const clone = baseTel.cloneNode(true);

        clone.querySelectorAll('input:not([type="radio"])').forEach(i => i.value = '');
        clone.querySelector('input[type="radio"]').value = idx;

        if (!clone.querySelector('.del-tel')) {
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'del-tel';
          del.textContent = '❌';
          clone.insertBefore(del, clone.firstChild);
        }

        tels.appendChild(clone);
        initTel(clone, idx);
      });

      // INSTITUCIONAL
      const tipoSel = document.getElementById('inst_tipo');
      const escuelaLab = document.getElementById('label_escuela');
      const formacionLab = document.getElementById('label_formacion');

      function toggleInstitucional() {
        const val = tipoSel.value;
        if (val === 'Director') {
          escuelaLab.style.display = 'block';
          formacionLab.style.display = 'none';
        } else if (val === 'Docente') {
          escuelaLab.style.display = 'block';
          formacionLab.style.display = 'block';
        } else if (val === 'Alumno') {
          escuelaLab.style.display = 'none';
          formacionLab.style.display = 'none';
        } else {
          // valor vacío: ocultar ambos
          escuelaLab.style.display = 'none';
          formacionLab.style.display = 'none';
        }
      }
      tipoSel.addEventListener('change', toggleInstitucional);
      toggleInstitucional(); // inicializa al cargar

    });
  </script>
</body>

</html>
<?php ob_end_flush(); ?>