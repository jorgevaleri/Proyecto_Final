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

// ASEGURAMOS QUE EXISTA SESION
if (empty($_SESSION['user_id'])) {
    header('Location: logeo.php');
    exit;
}

// ID Y ROL DE USUARIO
$uid = (int)$_SESSION['user_id'];
$session_role = $_SESSION['personas_rol'] ?? '';

// MENSAJE TEMPORAL
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_errors  = $_SESSION['flash_errors']  ?? [];
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

// MODO VER / EDITAR
$action = $_GET['action'] ?? 'view';
$errors = []; // errores locales (si no usamos flash)
$success = '';

// CARGAR DATOS
$stmt = mysqli_prepare($conexion, "
    SELECT u.usuarios_id, u.usuarios_email, u.usuarios_rol, u.usuarios_clave,
           p.personas_id, p.personas_apellido, p.personas_nombre, p.personas_dni,
           p.personas_fechnac, p.personas_sexo
    FROM usuarios u
    JOIN personas p ON u.personas_id = p.personas_id
    WHERE u.usuarios_id = ?
    LIMIT 1
");

// ENLAZAMOS Y EJECUTAMOS CON EL ID DE LA SESION
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

// GUARDAMOS EL ID DE LA PERSONA
$personas_id = (int)$user['personas_id'];

// CARGAMOS DATOS INSTITUCIONAL
$stmt = mysqli_prepare($conexion, "
    SELECT institucional_id, institucional_tipo, escuelas_id, formaciones_profesionales_id
      FROM institucional
     WHERE personas_id = ?
     LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $personas_id);
mysqli_stmt_execute($stmt);
$resInst = mysqli_stmt_get_result($stmt);
$inst = mysqli_fetch_assoc($resInst) ?: null;
mysqli_stmt_close($stmt);

// OTROS VALORES
$inst_id   = $inst['institucional_id'] ?? 0;
$inst_tipo = $inst['institucional_tipo'] ?? '';
$inst_esc  = isset($inst['escuelas_id']) ? (int)$inst['escuelas_id'] : 0;
$inst_form = isset($inst['formaciones_profesionales_id']) ? (int)$inst['formaciones_profesionales_id'] : 0;

// CARGAMOS DOMICILIOS / TELEFONOS
function get_domicilios($conexion, $personas_id)
{
    $q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id = " . intval($personas_id) . " ORDER BY domicilios_predeterminado DESC, domicilios_id DESC");
    return $q;
}
function get_telefonos($conexion, $personas_id)
{
    $q = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id = " . intval($personas_id) . " ORDER BY telefonos_predeterminado DESC, telefonos_id DESC");
    return $q;
}
$doms = get_domicilios($conexion, $personas_id);
$tels = get_telefonos($conexion, $personas_id);

// CARGAMOS ESCUELAS
$escuelas_q = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado = 0 ORDER BY escuelas_nombre");
$escuelas = mysqli_fetch_all($escuelas_q, MYSQLI_ASSOC);

// CRUD DOMICILIOS / TELEFONOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atype = $_POST['action_type'] ?? '';

    // AGREGAR DOMICILIO
    if ($atype === 'add_domicilio') {
        $calle = trim($_POST['domicilios_calle'] ?? '');
        $desc  = trim($_POST['domicilios_descripcion'] ?? '');
        $pred  = isset($_POST['domicilios_predeterminado']) ? 1 : 0;
        $lat   = trim($_POST['domicilios_latitud'] ?? '');
        $lon   = trim($_POST['domicilios_longitud'] ?? '');

        // VALIDACION
        if ($calle === '') {
            $_SESSION['flash_errors'] = ['La calle es obligatoria para el domicilio.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // SI SE MARCA PREDETERMINADO, SE LIMPIAN LOS OTROS
        if ($pred) {
            mysqli_query($conexion, "UPDATE domicilios SET domicilios_predeterminado = 0 WHERE personas_id = {$personas_id}");
        }

        // INSERTAR DOMICILIO
        $stmt = mysqli_prepare($conexion, "INSERT INTO domicilios (personas_id, domicilios_calle, domicilios_descripcion, domicilios_predeterminado, domicilios_latitud, domicilios_longitud) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ississ", $personas_id, $calle, $desc, $pred, $lat, $lon);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Domicilio agregado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // EDITAR DOMICILIO
    if ($atype === 'edit_domicilio') {
        $did  = intval($_POST['domicilios_id'] ?? 0);
        $calle = trim($_POST['domicilios_calle'] ?? '');
        $desc  = trim($_POST['domicilios_descripcion'] ?? '');
        $pred  = isset($_POST['domicilios_predeterminado']) ? 1 : 0;
        $lat   = trim($_POST['domicilios_latitud'] ?? '');
        $lon   = trim($_POST['domicilios_longitud'] ?? '');

        // VALIDACION
        if ($did <= 0 || $calle === '') {
            $_SESSION['flash_errors'] = ['Datos inválidos para editar domicilio.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // VERIFICAR PERTENENCIA DE DOMICILIO A LA PERSONA
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM domicilios WHERE domicilios_id = {$did} AND personas_id = {$personas_id}"));
        if (!$r || $r[0] == 0) {
            $_SESSION['flash_errors'] = ['No se encontró el domicilio solicitado.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        if ($pred) {
            mysqli_query($conexion, "UPDATE domicilios SET domicilios_predeterminado = 0 WHERE personas_id = {$personas_id}");
        }

        $stmt = mysqli_prepare($conexion, "UPDATE domicilios SET domicilios_calle = ?, domicilios_descripcion = ?, domicilios_predeterminado = ?, domicilios_latitud = ?, domicilios_longitud = ? WHERE domicilios_id = ?");
        mysqli_stmt_bind_param($stmt, "ssissi", $calle, $desc, $pred, $lat, $lon, $did);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Domicilio actualizado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // ELIMINAR DOMICILIO
    if ($atype === 'delete_domicilio') {
        $did = intval($_POST['domicilios_id'] ?? 0);
        if ($did <= 0) {
            $_SESSION['flash_errors'] = ['ID de domicilio inválido.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // VERIFICAR PERTENENCIA
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM domicilios WHERE domicilios_id = {$did} AND personas_id = {$personas_id}"));
        if (!$r || $r[0] == 0) {
            $_SESSION['flash_errors'] = ['Domicilio no encontrado o no te pertenece.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // ELIMINAR
        mysqli_query($conexion, "DELETE FROM domicilios WHERE domicilios_id = {$did}");
        $_SESSION['flash_success'] = 'Domicilio eliminado.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // AGREGAR TELEFONO
    if ($atype === 'add_telefono') {
        $num  = trim($_POST['telefonos_numero'] ?? '');
        $desc = trim($_POST['telefonos_descripcion'] ?? '');
        $pred = isset($_POST['telefonos_predeterminado']) ? 1 : 0;

        // VALIDACION
        if ($num === '') {
            $_SESSION['flash_errors'] = ['El número es obligatorio para agregar un teléfono.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        if ($pred) {
            mysqli_query($conexion, "UPDATE telefonos SET telefonos_predeterminado = 0 WHERE personas_id = {$personas_id}");
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO telefonos (personas_id, telefonos_numero, telefonos_descripcion, telefonos_predeterminado) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isss", $personas_id, $num, $desc, $pred);
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conexion, "INSERT INTO telefonos (personas_id, telefonos_numero, telefonos_descripcion, telefonos_predeterminado) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isss", $personas_id, $num, $desc, $pred);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Teléfono agregado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // EDITAR TELEFONO
    if ($atype === 'edit_telefono') {
        $tid  = intval($_POST['telefonos_id'] ?? 0);
        $num  = trim($_POST['telefonos_numero'] ?? '');
        $desc = trim($_POST['telefonos_descripcion'] ?? '');
        $pred = isset($_POST['telefonos_predeterminado']) ? 1 : 0;

        if ($tid <= 0 || $num === '') {
            $_SESSION['flash_errors'] = ['Datos inválidos para editar teléfono.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM telefonos WHERE telefonos_id = {$tid} AND personas_id = {$personas_id}"));
        if (!$r || $r[0] == 0) {
            $_SESSION['flash_errors'] = ['Teléfono no encontrado.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        if ($pred) {
            mysqli_query($conexion, "UPDATE telefonos SET telefonos_predeterminado = 0 WHERE personas_id = {$personas_id}");
        }

        $stmt = mysqli_prepare($conexion, "UPDATE telefonos SET telefonos_numero = ?, telefonos_descripcion = ?, telefonos_predeterminado = ? WHERE telefonos_id = ?");
        mysqli_stmt_bind_param($stmt, "ssii", $num, $desc, $pred, $tid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Teléfono actualizado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // ELIMINAR TELEFONO
    if ($atype === 'delete_telefono') {
        $tid = intval($_POST['telefonos_id'] ?? 0);
        if ($tid <= 0) {
            $_SESSION['flash_errors'] = ['ID de teléfono inválido.'];
            header('Location: perfil.php?action=edit');
            exit;
        }
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM telefonos WHERE telefonos_id = {$tid} AND personas_id = {$personas_id}"));
        if (!$r || $r[0] == 0) {
            $_SESSION['flash_errors'] = ['Teléfono no encontrado o no te pertenece.'];
            header('Location: perfil.php?action=edit');
            exit;
        }
        mysqli_query($conexion, "DELETE FROM telefonos WHERE telefonos_id = {$tid}");
        $_SESSION['flash_success'] = 'Teléfono eliminado.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // GUARDAR PERFIL COMPLETO
    if ($atype === 'save_profile') {

        // DATOS PERSONALES
        $apellido = trim($_POST['personas_apellido'] ?? '');
        $nombre   = trim($_POST['personas_nombre'] ?? '');
        $dni      = trim($_POST['personas_dni'] ?? '');
        $fechnac  = trim($_POST['personas_fechnac'] ?? '');
        $sexo     = trim($_POST['personas_sexo'] ?? '');

        // DATOS DE USUARIO
        $email    = trim($_POST['usuarios_email'] ?? '');
        $rol_post = $_POST['usuarios_rol'] ?? '';

        // INSTITUCIONAL
        $inst_tipo_post = trim($_POST['institucional_tipo'] ?? '');
        $escuela_post   = intval($_POST['escuelas_id'] ?? 0);
        $formacion_post = intval($_POST['formaciones_id'] ?? 0);

        // CONTASEÑA
        $actual   = $_POST['pass_actual'] ?? '';
        $nueva    = $_POST['pass_nueva'] ?? '';
        $conf     = $_POST['pass_conf'] ?? '';

        // VALIDACION
        $localErrors = [];
        if ($apellido === '' || $nombre === '') $localErrors[] = 'Apellido y nombre son obligatorios.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $localErrors[] = 'Email inválido.';

        // CONTROL DE ROLES (SOLO ADMINISTRADOR LO PUEDE MODIFICAR)
        $isAdmin = ($session_role === 'ADMINISTRADOR');
        if ($isAdmin) {
            if (!in_array($rol_post, ['ADMINISTRADOR', 'DIRECTOR', 'DOCENTE'])) $localErrors[] = 'Rol inválido.';
            $rol_to_save = $rol_post;
        } else {
            $rol_to_save = $user['usuarios_rol'];
        }

        // VALIDACIONES INSITUCIONAL BASICA
        if ($inst_tipo_post === '') $localErrors[] = 'Seleccioná un tipo institucional.';
        if ($escuela_post <= 0) $localErrors[] = 'Seleccioná una escuela válida.';
        if ($formacion_post <= 0) $localErrors[] = 'Seleccioná una formación profesional válida.';

        // VERIFICAR QUE LA FORMACION PERTENECE A LA ESCUELA
        $validFormForSchool = false;
        if ($escuela_post > 0 && $formacion_post > 0) {
            $cntQ = mysqli_query($conexion, "SELECT COUNT(*) FROM institucional WHERE escuelas_id = {$escuela_post} AND formaciones_profesionales_id = {$formacion_post}");
            $rowCnt = mysqli_fetch_row($cntQ);
            if ($rowCnt && (int)$rowCnt[0] > 0) $validFormForSchool = true;
        }
        if (!$validFormForSchool) $localErrors[] = 'La formación seleccionada no pertenece a la escuela indicada.';

        // VALIDACION DE CONTRASEÑA
        $update_password = false;
        if ($actual !== '' || $nueva !== '' || $conf !== '') {
            if ($actual === '' || $nueva === '' || $conf === '') {
                $localErrors[] = 'Completá actual y nueva contraseña y su confirmación para cambiar la clave.';
            } elseif ($nueva !== $conf) {
                $localErrors[] = 'La nueva contraseña y su confirmación no coinciden.';
            } elseif (strlen($nueva) < 6) {
                $localErrors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } else {

                // VERIFICAR CONTRASEÑA ACTUAL
                if (!password_verify($actual, $user['usuarios_clave'])) {
                    $localErrors[] = 'La contraseña actual no coincide.';
                } else {
                    $update_password = true;
                }
            }
        }

        if (!empty($localErrors)) {
            $_SESSION['flash_errors'] = $localErrors;
            header('Location: perfil.php?action=edit');
            exit;
        }

        // ACTUALIZACION EN LA BASE DE DATOS (SIN ERRORES)

        // TABLA PERSONAS
        $stmt = mysqli_prepare($conexion, "UPDATE personas SET personas_apellido = ?, personas_nombre = ?, personas_dni = ?, personas_fechnac = ?, personas_sexo = ? WHERE personas_id = ?");
        mysqli_stmt_bind_param($stmt, "sssssi", $apellido, $nombre, $dni, $fechnac, $sexo, $personas_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // TABLA USUARIOS
        if ($update_password) {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET usuarios_email = ?, usuarios_rol = ?, usuarios_clave = ? WHERE usuarios_id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $email, $rol_to_save, $hash, $uid);
        } else {
            $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET usuarios_email = ?, usuarios_rol = ? WHERE usuarios_id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $email, $rol_to_save, $uid);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // TABLA INSTITUCIONAL
        if ($inst_id > 0) {
            $stmt = mysqli_prepare($conexion, "UPDATE institucional SET institucional_tipo = ?, escuelas_id = ?, formaciones_profesionales_id = ? WHERE institucional_id = ?");
            mysqli_stmt_bind_param($stmt, "siii", $inst_tipo_post, $escuela_post, $formacion_post, $inst_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conexion, "INSERT INTO institucional (personas_id, institucional_tipo, escuelas_id, formaciones_profesionales_id) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "issi", $personas_id, $inst_tipo_post, $escuela_post, $formacion_post);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $inst_id = mysqli_insert_id($conexion);
        }

        // TABLA DOMICILIOS
        mysqli_query($conexion, "DELETE FROM domicilios WHERE personas_id = {$personas_id}");

        $doms_calle = $_POST['domicilios_calle'] ?? [];
        $doms_desc  = $_POST['domicilios_descripcion'] ?? [];
        $doms_lat   = $_POST['domicilios_latitud'] ?? [];
        $doms_lon   = $_POST['domicilios_longitud'] ?? [];
        $doms_pred_index = $_POST['domicilios_predeterminado'] ?? 0;

        foreach ($doms_calle as $i => $calle_val) {
            $c = mysqli_real_escape_string($conexion, trim($calle_val));
            if ($c === '') continue; // saltar vacíos
            $d = mysqli_real_escape_string($conexion, trim($doms_desc[$i] ?? ''));
            $la = mysqli_real_escape_string($conexion, trim($doms_lat[$i] ?? ''));
            $ln = mysqli_real_escape_string($conexion, trim($doms_lon[$i] ?? ''));
            $pr = ((string)$i === (string)$doms_pred_index) ? 1 : 0;
            $sql = "INSERT INTO domicilios (domicilios_calle,domicilios_descripcion,domicilios_latitud,domicilios_longitud,domicilios_predeterminado,personas_id)
                VALUES ('$c','$d','$la','$ln',$pr,$personas_id)";
            mysqli_query($conexion, $sql) or die(mysqli_error($conexion));
        }

        // TABLA TELEFONOS
        mysqli_query($conexion, "DELETE FROM telefonos WHERE personas_id = {$personas_id}");

        $tels_num = $_POST['telefonos_numero'] ?? [];
        $tels_desc = $_POST['telefonos_descripcion'] ?? [];
        $tels_pred_index = $_POST['telefonos_predeterminado'] ?? 0;

        foreach ($tels_num as $i => $num_val) {
            $n = mysqli_real_escape_string($conexion, trim($num_val));
            if ($n === '') continue;
            $d = mysqli_real_escape_string($conexion, trim($tels_desc[$i] ?? ''));
            $pr = ((string)$i === (string)$tels_pred_index) ? 1 : 0;
            $sql = "INSERT INTO telefonos (telefonos_numero,telefonos_descripcion,telefonos_predeterminado,personas_id)
                VALUES ('$n','$d',$pr,$personas_id)";
            mysqli_query($conexion, $sql) or die(mysqli_error($conexion));
        }

        // ACTUALIZAR SESION CON LOS CAMBIOS
        $_SESSION['personas_nombre'] = $nombre;
        $_SESSION['personas_apellido'] = $apellido;
        $_SESSION['personas_rol'] = $rol_to_save;
        $_SESSION['usuarios_email'] = $email;

        $_SESSION['flash_success'] = 'Perfil guardado correctamente.';
        header('Location: perfil.php?updated=1');
        exit;
    }
}

// MANEJO DE DELETES VIA GET
if (isset($_GET['do']) && isset($_GET['id'])) {
    $do = $_GET['do'];
    $id = intval($_GET['id']);

    if ($do === 'del_domicilio' && $id > 0) {
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM domicilios WHERE domicilios_id = {$id} AND personas_id = {$personas_id}"));
        if ($r && $r[0] > 0) {
            mysqli_query($conexion, "DELETE FROM domicilios WHERE domicilios_id = {$id}");
            $_SESSION['flash_success'] = 'Domicilio eliminado.';
        } else {
            $_SESSION['flash_errors'] = ['Domicilio no encontrado o no te pertenece.'];
        }
        header('Location: perfil.php?action=edit');
        exit;
    }

    if ($do === 'del_telefono' && $id > 0) {
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM telefonos WHERE telefonos_id = {$id} AND personas_id = {$personas_id}"));
        if ($r && $r[0] > 0) {
            mysqli_query($conexion, "DELETE FROM telefonos WHERE telefonos_id = {$id}");
            $_SESSION['flash_success'] = 'Teléfono eliminado.';
        } else {
            $_SESSION['flash_errors'] = ['Teléfono no encontrado o no te pertenece.'];
        }
        header('Location: perfil.php?action=edit');
        exit;
    }
}

// RECARGAR LISTA DESPUES DE LOS CAMBIOS
$doms = get_domicilios($conexion, $personas_id);
$tels = get_telefonos($conexion, $personas_id);

// RECUPERAR FLASH MESSAGES SI VIENE
$flash_success = $_SESSION['flash_success'] ?? $flash_success;
$flash_errors  = $_SESSION['flash_errors']  ?? $flash_errors;
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

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

<body class="body">
    <main class="fp-page">
        <h1 class="title">Mi Perfil</h1>

        <?php if ($action === 'view'): ?>
            <div class="view-panel">

                <!-- DATOS USUARIOS -->
                <h3>Datos Usuario</h3>
                <div class="row">
                    <label>Email <input value="<?= htmlspecialchars($user['usuarios_email']) ?>" disabled></label>
                    <label>Rol <input value="<?= htmlspecialchars($user['usuarios_rol'] ?? '-') ?>" disabled></label>
                </div>

                <!-- DATOS PERSONALES -->
                <h3>Datos Personales</h3>
                <div class="row">
                    <label>Apellido <input value="<?= htmlspecialchars($user['personas_apellido']) ?>" disabled></label>
                    <label>Nombre <input value="<?= htmlspecialchars($user['personas_nombre']) ?>" disabled></label>
                    <label>DNI <input value="<?= htmlspecialchars($user['personas_dni']) ?>" disabled></label>
                    <label>Fecha Nac. <input type="date" value="<?= htmlspecialchars($user['personas_fechnac']) ?>" disabled></label>
                    <label>Sexo <input value="<?= htmlspecialchars($user['personas_sexo']) ?>" disabled></label>
                </div>

                <!-- INSTITUCIONAL -->
                <h3>Institucional</h3>
                <div class="row">
                    <label>Tipo institucional <input value="<?= htmlspecialchars($inst_tipo ?: 'No registrado') ?>" disabled></label>
                    <label>Escuela
                        <?php
                        $escName = '-';
                        foreach ($escuelas as $e) {
                            if ($e['escuelas_id'] == $inst_esc) {
                                $escName = $e['escuelas_nombre'];
                                break;
                            }
                        }
                        ?>
                        <input value="<?= htmlspecialchars($escName) ?>" disabled>
                    </label>
                    <label>Formación profesional
                        <?php
                        $formacion_nombre = '-';
                        if (!empty($inst) && !empty($inst['formaciones_profesionales_id'])) {
                            $fid = (int)$inst['formaciones_profesionales_id'];
                            $qf = mysqli_query($conexion, "SELECT formaciones_profesionales_nombre FROM formaciones_profesionales WHERE formaciones_profesionales_id = {$fid} LIMIT 1");
                            if ($qf && mysqli_num_rows($qf)) {
                                $rowf = mysqli_fetch_assoc($qf);
                                $formacion_nombre = $rowf['formaciones_profesionales_nombre'] ?? '-';
                            }
                        }
                        ?>
                        <input value="<?= htmlspecialchars($formacion_nombre) ?>" disabled>
                    </label>
                </div>

                <!-- DOMICILIOS -->
                <h3>Domicilios</h3>
                <div class="blocks">
                    <?php
                    $doms_view_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id = {$personas_id} ORDER BY domicilios_predeterminado DESC, domicilios_id DESC");
                    if (!$doms_view_q || mysqli_num_rows($doms_view_q) === 0): ?>
                        <p>No hay domicilios registrados.</p>
                    <?php else: ?>
                        <?php while ($d = mysqli_fetch_assoc($doms_view_q)): ?>
                            <div class="dom-block">
                                <label>Calle y número<br><input value="<?= htmlspecialchars($d['domicilios_calle'] ?? '') ?>" disabled></label>
                                <label>Descripción<br><input value="<?= htmlspecialchars($d['domicilios_descripcion'] ?? '') ?>" disabled></label>
                                <label>Predeterminado<br><input value="<?= (!empty($d['domicilios_predeterminado']) ? 'Sí' : 'No') ?>" disabled></label>

                                <?php
                                $lat = trim($d['domicilios_latitud'] ?? '');
                                $lon = trim($d['domicilios_longitud'] ?? '');
                                ?>
                                <?php if ($lat !== '' && $lon !== ''): ?>
                                    <div class="map" style="margin-top:10px; height:180px;">
                                        <iframe
                                            width="100%"
                                            height="180"
                                            frameborder="0"
                                            style="border:0"
                                            src="https://maps.google.com/maps?q=<?= rawurlencode($lat) ?>,<?= rawurlencode($lon) ?>&z=15&output=embed"
                                            loading="lazy"
                                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <!-- TELEFONOS -->
                <h3>Teléfonos</h3>
                <div class="blocks">
                    <?php if ($tels && mysqli_num_rows($tels) === 0): ?>
                        <p>No se encontraron teléfonos.</p>
                    <?php endif; ?>
                    <?php while ($t = $tels ? mysqli_fetch_assoc($tels) : null): if (!$t) break; ?>
                        <div class="tel-block">
                            <label>Teléfono <input value="<?= htmlspecialchars($t['telefonos_numero']) ?>" disabled></label>
                            <label>Desc. <input value="<?= htmlspecialchars($t['telefonos_descripcion']) ?>" disabled></label>
                            <label>Predeterminado <input value="<?= $t['telefonos_predeterminado'] ? 'Sí' : 'No' ?>" disabled></label>
                        </div>
                    <?php endwhile ?>
                </div>
            </div>

            <!-- BOTONES -->
            <div class="actions-bottom" style="margin-top:14px;">
                <a class="pill" href="menu_principal.php"><i class="bi bi-arrow-left"></i> Volver</a>
                <a class="pill primary" href="perfil.php?action=edit"><i class="bi bi-pencil"></i> Editar mis datos</a>
            </div>

            <!-- FORMULARIO EDITAR -->
        <?php elseif ($action === 'edit'): ?>
            <form id="personForm" method="post" action="perfil.php?action=edit" novalidate>

                <div id="form-errors" class="field-error hidden" role="alert" aria-live="polite"></div>

                <input type="hidden" name="action_type" value="save_profile">

                <!-- DATOS USUARIO -->
                <h3>Datos Usuario</h3>
                <div class="row">
                    <label>Email
                        <input type="email" name="usuarios_email" id="usuarios_email"
                            value="<?= htmlspecialchars($_POST['usuarios_email'] ?? $user['usuarios_email'] ?? '') ?>">
                    </label>

                    <?php if (($session_role ?? '') === 'ADMINISTRADOR'): ?>
                        <label>Rol
                            <select name="usuarios_rol" id="usuarios_rol" required>
                                <option value="ADMINISTRADOR" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol'] ?? '') === 'ADMINISTRADOR') ? 'selected' : '' ?>>ADMINISTRADOR</option>
                                <option value="DIRECTOR" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol'] ?? '') === 'DIRECTOR') ? 'selected' : '' ?>>DIRECTOR</option>
                                <option value="DOCENTE" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol'] ?? '') === 'DOCENTE') ? 'selected' : '' ?>>DOCENTE</option>
                            </select>
                        </label>
                    <?php else: ?>
                        <label>Rol
                            <input type="text" readonly value="<?= htmlspecialchars($user['usuarios_rol'] ?? '-') ?>">
                            <small class="note">Sólo administradores pueden cambiar el rol.</small>
                        </label>
                    <?php endif; ?>
                </div>

                <!-- CAMBIAR CONTRASEÑA (OPCIONAL) -->
                <div style="margin-top:10px;">
                    <legend style="font-size:0.95rem; font-weight:600; margin-bottom:6px;">Cambiar contraseña (opcional)</legend>
                    <div class="row">
                        <label>Contraseña actual
                            <input type="password" name="pass_actual" id="pass_actual" placeholder="Si no cambia, dejalo vacío">
                        </label>
                        <label>Nueva contraseña
                            <input type="password" name="pass_nueva" id="pass_nueva" placeholder="Mínimo 6 caracteres">
                        </label>
                        <label>Confirmar nueva
                            <input type="password" name="pass_conf" id="pass_conf" placeholder="">
                        </label>
                    </div>
                </div>

                <!-- DATOS PERSONALES -->
                <h3>Datos Personales</h3>
                <div class="row">
                    <label>Apellido
                        <input name="personas_apellido" id="personas_apellido" value="<?= htmlspecialchars($user['personas_apellido'] ?? '') ?>">
                    </label>

                    <label>Nombre
                        <input name="personas_nombre" id="personas_nombre" value="<?= htmlspecialchars($user['personas_nombre'] ?? '') ?>">
                    </label>
                </div>

                <div class="row">
                    <label>DNI
                        <input name="personas_dni" id="personas_dni" value="<?= htmlspecialchars($user['personas_dni'] ?? '') ?>">
                    </label>

                    <label>Fecha Nac.
                        <input type="date" name="personas_fechnac" id="personas_fechnac" value="<?= htmlspecialchars($user['personas_fechnac'] ?? '') ?>">
                    </label>

                    <label>Sexo
                        <select name="personas_sexo" id="personas_sexo">
                            <option value="">--</option>
                            <option value="Masculino" <?= ($user['personas_sexo'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino" <?= ($user['personas_sexo'] ?? '') === 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                        </select>
                    </label>
                </div>

                <!-- DOMICILIOS -->
                <h3>Domicilios</h3>
                <div id="doms" class="blocks">
                    <?php
                    $domsArr = [];
                    $doms_q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id = {$personas_id} ORDER BY domicilios_predeterminado DESC, domicilios_id DESC");
                    if ($doms_q && mysqli_num_rows($doms_q) > 0) {
                        $domsArr = mysqli_fetch_all($doms_q, MYSQLI_ASSOC);
                    }
                    ?>

                    <?php if (!empty($domsArr)): ?>
                        <?php foreach ($domsArr as $i => $d): ?>
                            <div class="dom-block">
                                <?php if ($i > 0): ?><button type="button" class="del-dom">❌</button><?php endif; ?>
                                <label>Calle y número<br><input name="domicilios_calle[]" placeholder="Calle y número" value="<?= htmlspecialchars($d['domicilios_calle'] ?? '') ?>"></label>
                                <label>Descripción<br><input name="domicilios_descripcion[]" placeholder="Descripción" value="<?= htmlspecialchars($d['domicilios_descripcion'] ?? '') ?>"></label>
                                <label>Predeterminado <input type="radio" name="domicilios_predeterminado" value="<?= $i ?>" <?= (!empty($d['domicilios_predeterminado']) ? 'checked' : '') ?>></label>
                                <label>Buscar dirección<br><input class="map-search" placeholder="Buscar dirección" value=""></label>
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

                <!-- TELEFONOS -->
                <h3>Teléfonos</h3>
                <div id="tels" class="blocks">
                    <?php
                    $telsArr = [];
                    $tels_q = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id = {$personas_id} ORDER BY telefonos_predeterminado DESC, telefonos_id DESC");
                    if ($tels_q && mysqli_num_rows($tels_q) > 0) $telsArr = mysqli_fetch_all($tels_q, MYSQLI_ASSOC);
                    ?>

                    <?php if (!empty($telsArr)): ?>
                        <?php foreach ($telsArr as $i => $t): ?>
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
                <div class="row">
                    <label>Tipo institucional
                        <select name="institucional_tipo" id="institucional_tipo">
                            <?php
                            $tipos = ['Alumno' => 'Alumno', 'Docente' => 'Docente', 'Director' => 'Director'];
                            $sel_val = $_POST['institucional_tipo'] ?? $inst_tipo;
                            foreach ($tipos as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>" <?= ($sel_val === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Escuela
                        <select name="escuelas_id" id="escuelas_id">
                            <option value="">-- Seleccione escuela --</option>
                            <?php foreach ($escuelas as $e): ?>
                                <option value="<?= $e['escuelas_id'] ?>" <?= (($_POST['escuelas_id'] ?? $inst_esc) == $e['escuelas_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['escuelas_nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Formación Profesional
                        <select name="formaciones_id" id="formaciones_id">
                            <option value="">-- Seleccione formación --</option>
                        </select>
                    </label>
                </div>

                <!-- BOTONES -->
                <div class="actions" style="margin-top:16px;">
                    <button type="submit" class="pill primary">Guardar cambios</button>
                    <a href="perfil.php" class="pill">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <!-- FOOTER -->
    <?php include('footer.php'); ?>

    <!-- SWEETALERT2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php
    // ENDPOINTS ABSOLUTOS QUE USA JS
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $dir    = dirname($_SERVER['SCRIPT_NAME']);
    if ($dir === '/' || $dir === '\\') $dir = '';
    $geocodeEndpoint = $scheme . '://' . $host . $dir . '/registrarse.php?action=geocode';
    $checkDniEndpoint = $scheme . '://' . $host . $dir . '/registrarse.php?action=check_dni';
    ?>

    <script>
        // VARIABLES QUE JS UTILIZARA
        window.PERFIL_INST_ESC = <?= json_encode((int)$inst_esc) ?>;
        window.PERFIL_INST_FORM = <?= json_encode((int)$inst_form) ?>;
        window.PERFIL_FLASH_SUCCESS = <?= json_encode($flash_success ?? null, JSON_UNESCAPED_UNICODE) ?>;
        window.PERFIL_FLASH_ERRORS = <?= json_encode(array_values($flash_errors ?? []), JSON_UNESCAPED_UNICODE) ?>;
        window.PERFIL_UPDATED_FLAG = <?= isset($_GET['updated']) ? 'true' : 'false' ?>;

        // NUEVOS ENDPOINTS USADOS POR JS
        window.GEOCODE_ENDPOINT = <?= json_encode($geocodeEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.CHECK_DNI_ENDPOINT = <?= json_encode($checkDniEndpoint, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <!-- SCRIPT -->
    <script src="JS/perfil.js" defer></script>
</body>

</html>