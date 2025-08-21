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
// perfil.php - Perfil de usuario con edición, institucional y CRUD de domicilios/telefonos
if (session_status() === PHP_SESSION_NONE) session_start();
include('../BackEnd/conexion.php'); // ajustá la ruta si hace falta

// Requiere usuario logueado
if (empty($_SESSION['user_id'])) {
    header('Location: logeo.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
// rol del usuario conectado (para permisos)
$session_role = $_SESSION['personas_rol'] ?? '';

// Flash messages via session
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_errors  = $_SESSION['flash_errors']  ?? [];
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

// modo: view | edit
$action = $_GET['action'] ?? 'view';
$errors = []; // errores locales (si no usamos flash)
$success = '';

// ------------------------
// Cargar datos del usuario
// ------------------------
$stmt = mysqli_prepare($conexion, "
    SELECT u.usuarios_id, u.usuarios_email, u.usuarios_rol, u.usuarios_clave,
           p.personas_id, p.personas_apellido, p.personas_nombre, p.personas_dni,
           p.personas_fechnac, p.personas_sexo
    FROM usuarios u
    JOIN personas p ON u.personas_id = p.personas_id
    WHERE u.usuarios_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

$personas_id = (int)$user['personas_id'];

// institucional (fila única por persona, si existe)
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

$inst_id   = $inst['institucional_id'] ?? 0;
$inst_tipo = $inst['institucional_tipo'] ?? '';
$inst_esc  = isset($inst['escuelas_id']) ? (int)$inst['escuelas_id'] : 0;
$inst_form = isset($inst['formaciones_profesionales_id']) ? (int)$inst['formaciones_profesionales_id'] : 0;

// domicilios / telefonos (lista actual)
function get_domicilios($conexion, $personas_id) {
    $q = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id = " . intval($personas_id) . " ORDER BY domicilios_predeterminado DESC, domicilios_id DESC");
    return $q;
}
function get_telefonos($conexion, $personas_id) {
    $q = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id = " . intval($personas_id) . " ORDER BY telefonos_predeterminado DESC, telefonos_id DESC");
    return $q;
}
$doms = get_domicilios($conexion, $personas_id);
$tels = get_telefonos($conexion, $personas_id);

// escuelas (para select)
$escuelas_q = mysqli_query($conexion, "SELECT escuelas_id, escuelas_nombre FROM escuelas WHERE escuelas_eliminado = 0 ORDER BY escuelas_nombre");
$escuelas = mysqli_fetch_all($escuelas_q, MYSQLI_ASSOC);

// ----------------------------------------------------------------
// Procesar acciones CRUD (domicilios/telefonos) y guardado de perfil
// ----------------------------------------------------------------
// Utilizamos 'action_type' en los forms para distinguir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atype = $_POST['action_type'] ?? '';

    // -----------------------
    // Añadir domicilio
    // -----------------------
    if ($atype === 'add_domicilio') {
        $calle = trim($_POST['domicilios_calle'] ?? '');
        $desc  = trim($_POST['domicilios_descripcion'] ?? '');
        $pred  = isset($_POST['domicilios_predeterminado']) ? 1 : 0;
        $lat   = trim($_POST['domicilios_latitud'] ?? '');
        $lon   = trim($_POST['domicilios_longitud'] ?? '');

        if ($calle === '') {
            $_SESSION['flash_errors'] = ['La calle es obligatoria para el domicilio.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // si predeterminado, resetear otros
        if ($pred) {
            mysqli_query($conexion, "UPDATE domicilios SET domicilios_predeterminado = 0 WHERE personas_id = {$personas_id}");
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO domicilios (personas_id, domicilios_calle, domicilios_descripcion, domicilios_predeterminado, domicilios_latitud, domicilios_longitud) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "ississ", $personas_id, $calle, $desc, $pred, $lat, $lon);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Domicilio agregado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // -----------------------
    // Editar domicilio
    // -----------------------
    if ($atype === 'edit_domicilio') {
        $did  = intval($_POST['domicilios_id'] ?? 0);
        $calle = trim($_POST['domicilios_calle'] ?? '');
        $desc  = trim($_POST['domicilios_descripcion'] ?? '');
        $pred  = isset($_POST['domicilios_predeterminado']) ? 1 : 0;
        $lat   = trim($_POST['domicilios_latitud'] ?? '');
        $lon   = trim($_POST['domicilios_longitud'] ?? '');

        if ($did <= 0 || $calle === '') {
            $_SESSION['flash_errors'] = ['Datos inválidos para editar domicilio.'];
            header('Location: perfil.php?action=edit');
            exit;
        }

        // verificar que el domicilio pertenece a la persona
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

    // -----------------------
    // Eliminar domicilio (via POST)
    // -----------------------
    if ($atype === 'delete_domicilio') {
        $did = intval($_POST['domicilios_id'] ?? 0);
        if ($did <= 0) {
            $_SESSION['flash_errors'] = ['ID de domicilio inválido.'];
            header('Location: perfil.php?action=edit');
            exit;
        }
        // verificar pertenencia
        $r = mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM domicilios WHERE domicilios_id = {$did} AND personas_id = {$personas_id}"));
        if (!$r || $r[0] == 0) {
            $_SESSION['flash_errors'] = ['Domicilio no encontrado o no te pertenece.'];
            header('Location: perfil.php?action=edit');
            exit;
        }
        mysqli_query($conexion, "DELETE FROM domicilios WHERE domicilios_id = {$did}");
        $_SESSION['flash_success'] = 'Domicilio eliminado.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // -----------------------
    // Añadir teléfono
    // -----------------------
    if ($atype === 'add_telefono') {
        $num  = trim($_POST['telefonos_numero'] ?? '');
        $desc = trim($_POST['telefonos_descripcion'] ?? '');
        $pred = isset($_POST['telefonos_predeterminado']) ? 1 : 0;

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
        // Note: telefonos_predeterminado may be tinyint; bind as string is ok, but better as int:
        // We'll re-prepare with correct types:
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conexion, "INSERT INTO telefonos (personas_id, telefonos_numero, telefonos_descripcion, telefonos_predeterminado) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isss", $personas_id, $num, $desc, $pred);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['flash_success'] = 'Teléfono agregado correctamente.';
        header('Location: perfil.php?action=edit');
        exit;
    }

    // -----------------------
    // Editar teléfono
    // -----------------------
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

    // -----------------------
    // Eliminar teléfono
    // -----------------------
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

    // -----------------------
    // Guardar perfil completo (personas, usuarios, institucional)
    // -----------------------
    if ($atype === 'save_profile') {
        // Datos de persona
        $apellido = trim($_POST['personas_apellido'] ?? '');
        $nombre   = trim($_POST['personas_nombre'] ?? '');
        $dni      = trim($_POST['personas_dni'] ?? '');
        $fechnac  = trim($_POST['personas_fechnac'] ?? '');
        $sexo     = trim($_POST['personas_sexo'] ?? '');

        // Datos de usuario
        $email    = trim($_POST['usuarios_email'] ?? '');
        $rol_post = $_POST['usuarios_rol'] ?? '';

        // Institucional
        $inst_tipo_post = trim($_POST['institucional_tipo'] ?? '');
        $escuela_post   = intval($_POST['escuelas_id'] ?? 0);
        $formacion_post = intval($_POST['formaciones_id'] ?? 0);

        // Contraseña
        $actual   = $_POST['pass_actual'] ?? '';
        $nueva    = $_POST['pass_nueva'] ?? '';
        $conf     = $_POST['pass_conf'] ?? '';

        // VALIDACIONES
        $localErrors = [];
        if ($apellido === '' || $nombre === '') $localErrors[] = 'Apellido y nombre son obligatorios.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $localErrors[] = 'Email inválido.';

        // Si el usuario NO es admin, ignoro cualquier cambio de rol y lo dejo igual
        $isAdmin = ($session_role === 'ADMINISTRADOR');
        if ($isAdmin) {
            if (!in_array($rol_post, ['ADMINISTRADOR', 'DIRECTOR', 'DOCENTE'])) $localErrors[] = 'Rol inválido.';
            $rol_to_save = $rol_post;
        } else {
            // no admin -> mantiene el rol actual del usuario conectado
            $rol_to_save = $user['usuarios_rol'];
        }

        // Validaciones institucionales
        if ($inst_tipo_post === '') $localErrors[] = 'Seleccioná un tipo institucional.';
        if ($escuela_post <= 0) $localErrors[] = 'Seleccioná una escuela válida.';
        if ($formacion_post <= 0) $localErrors[] = 'Seleccioná una formación profesional válida.';

        // Verificar que la formación pertenece a la escuela seleccionada:
        // comprobamos que existe al menos una fila en 'institucional' que asocie esa formacion y escuela,
        // ya que el endpoint formaciones usa institucional para listar. Si no existe, consideramos inválido.
        $validFormForSchool = false;
        if ($escuela_post > 0 && $formacion_post > 0) {
            $cntQ = mysqli_query($conexion, "SELECT COUNT(*) FROM institucional WHERE escuelas_id = {$escuela_post} AND formaciones_profesionales_id = {$formacion_post}");
            $rowCnt = mysqli_fetch_row($cntQ);
            if ($rowCnt && (int)$rowCnt[0] > 0) $validFormForSchool = true;
        }
        if (!$validFormForSchool) $localErrors[] = 'La formación seleccionada no pertenece a la escuela indicada.';

        // Validaciones de contraseña
        $update_password = false;
        if ($actual !== '' || $nueva !== '' || $conf !== '') {
            if ($actual === '' || $nueva === '' || $conf === '') {
                $localErrors[] = 'Completá actual y nueva contraseña y su confirmación para cambiar la clave.';
            } elseif ($nueva !== $conf) {
                $localErrors[] = 'La nueva contraseña y su confirmación no coinciden.';
            } elseif (strlen($nueva) < 6) {
                $localErrors[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
            } else {
                // verificar contraseña actual
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

        // Si llegamos aquí, actualizamos las tablas
        // 1) personas
        $stmt = mysqli_prepare($conexion, "UPDATE personas SET personas_apellido = ?, personas_nombre = ?, personas_dni = ?, personas_fechnac = ?, personas_sexo = ? WHERE personas_id = ?");
        mysqli_stmt_bind_param($stmt, "sssssi", $apellido, $nombre, $dni, $fechnac, $sexo, $personas_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // 2) usuarios (si no admin, el rol queda igual)
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

        // 3) institucional insert/update
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

        // actualizar sesión (nombre/apellido/rol/email)
        $_SESSION['personas_nombre'] = $nombre;
        $_SESSION['personas_apellido'] = $apellido;
        $_SESSION['personas_rol'] = $rol_to_save;
        $_SESSION['usuarios_email'] = $email;

        $_SESSION['flash_success'] = 'Perfil guardado correctamente.';
        header('Location: perfil.php?updated=1');
        exit;
    }

    // si no matcheó action_type, ignoramos y continuamos
}

// detectar deletes vía GET (por comodidad): del_domicilio, del_telefono
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

// recargar listas después de posibles cambios
$doms = get_domicilios($conexion, $personas_id);
$tels = get_telefonos($conexion, $personas_id);

// recoger flash si vienen por redirect
$flash_success = $_SESSION['flash_success'] ?? $flash_success;
$flash_errors  = $_SESSION['flash_errors']  ?? $flash_errors;
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

// ----------------------
// HTML / Vista
// ----------------------
?>
<!DOCTYPE html>
<html lang="es">
<?php include('head.php'); ?>

<!-- ESTILOS -->
 <link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_app.css">
<style>
/* TOAST styles */
#toast-container { position: fixed; top: 16px; right: 16px; z-index: 9999; display:flex; flex-direction:column; gap:10px; }
.toast { min-width: 240px; max-width:360px; padding:10px 14px; border-radius:8px; color:#fff; box-shadow:0 4px 10px rgba(0,0,0,0.12); opacity:0; transform:translateY(-8px); transition: all .25s ease; }
.toast.show { opacity:1; transform:translateY(0); }
.toast.success { background: #198754; }
.toast.error { background: #dc3545; }
/* layout for profile actions */
.perfil-actions { display:flex; gap:10px; justify-content:flex-end; margin:12px 0; }
.perfil-edit-actions { display:flex; gap:10px; justify-content:flex-end; align-items:center; margin:12px 0 18px; }
.btn { padding:8px 12px; border-radius:6px; text-decoration:none; display:inline-flex; gap:8px; align-items:center; border:1px solid transparent; }
.btn-primary { background:#0d6efd; color:#fff; border-color:#0d6efd; }
.btn-secondary { background:#f8f9fa; color:#212529; border-color:#ced4da; }
.table-small { width:100%; border-collapse:collapse; margin-top:8px; }
.table-small th,.table-small td { border:1px solid #ddd; padding:6px; font-size:14px; }
.form-inline { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.form-inline label { display:flex; flex-direction:column; font-size:13px; }
small.note { color:#666; display:block; margin-top:6px; }
</style>
<?php include('header.php'); ?>
<?php include('menu_lateral.php'); ?>

<body class="body">
    <div id="toast-container"></div>

    <main class="fp-page">
        <h1>Mi Perfil</h1>

        <?php if ($action === 'view'): ?>
            <div class="perfil-actions">
                <a class="btn btn-secondary" href="javascript:history.back()"><i class="bi bi-arrow-left"></i> Volver</a>
                <a class="btn btn-primary" href="perfil.php?action=edit"><i class="bi bi-pencil"></i> Editar mis datos</a>
            </div>
        <?php endif; ?>

        <?php if ($action === 'view'): ?>
            <section class="perfil-datos">
                <h2>Datos Usuario</h2>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['usuarios_email']) ?></p>
                <p><strong>Rol:</strong> <?= htmlspecialchars($user['usuarios_rol'] ?? '-') ?></p>

                <h2>Datos Personales</h2>
                <p><strong>Apellido:</strong> <?= htmlspecialchars($user['personas_apellido']) ?></p>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($user['personas_nombre']) ?></p>
                <p><strong>DNI:</strong> <?= htmlspecialchars($user['personas_dni']) ?></p>
                <p><strong>Fecha Nac.:</strong> <?= htmlspecialchars($user['personas_fechnac']) ?></p>
                <p><strong>Sexo:</strong> <?= htmlspecialchars($user['personas_sexo']) ?></p>

                <h2>Institucional</h2>
                <p><strong>Tipo institucional:</strong> <?= htmlspecialchars($inst_tipo ?: 'No registrado') ?></p>
                <p><strong>Escuela:</strong>
                    <?php
                    $escName = '-';
                    foreach ($escuelas as $e) {
                        if ($e['escuelas_id'] == $inst_esc) { $escName = $e['escuelas_nombre']; break; }
                    }
                    echo htmlspecialchars($escName);
                    ?>
                </p>
                <p><strong>Formación profesional (ID):</strong> <?= htmlspecialchars($inst_form ?: '-') ?></p>

                <h2>Domicilios</h2>
                <?php if ($doms && mysqli_num_rows($doms) === 0): ?>
                    <p>No se encontraron domicilios.</p>
                <?php endif; ?>
                <?php while ($d = $doms ? mysqli_fetch_assoc($doms) : null): if (!$d) break; ?>
                    <div class="dom-block">
                        <p><strong>Calle:</strong> <?= htmlspecialchars($d['domicilios_calle']) ?></p>
                        <p><strong>Desc.:</strong> <?= htmlspecialchars($d['domicilios_descripcion']) ?></p>
                        <p><strong>Predeterminado:</strong> <?= $d['domicilios_predeterminado'] ? 'Sí' : 'No' ?></p>
                    </div>
                <?php endwhile ?>

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
            </section>

        <?php elseif ($action === 'edit'): ?>
            <section class="perfil-editar">
                <!-- botones arriba -->
                <form method="post" action="perfil.php?action=edit" id="perfil-edit-form">
                    <input type="hidden" name="action_type" value="save_profile">
                    <div class="perfil-edit-actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar cambios</button>
                        <a class="btn btn-secondary" href="perfil.php"><i class="bi bi-x"></i> Cancelar</a>
                    </div>

                    <fieldset>
                        <legend>Datos personales</legend>
                        <div class="form-inline">
                            <label>Apellido
                                <input type="text" name="personas_apellido" value="<?= htmlspecialchars($_POST['personas_apellido'] ?? $user['personas_apellido']) ?>" required>
                            </label>
                            <label>Nombre
                                <input type="text" name="personas_nombre" value="<?= htmlspecialchars($_POST['personas_nombre'] ?? $user['personas_nombre']) ?>" required>
                            </label>
                            <label>DNI
                                <input type="text" name="personas_dni" value="<?= htmlspecialchars($_POST['personas_dni'] ?? $user['personas_dni']) ?>">
                            </label>
                            <label>Fecha Nac.
                                <input type="date" name="personas_fechnac" value="<?= htmlspecialchars($_POST['personas_fechnac'] ?? $user['personas_fechnac']) ?>">
                            </label>
                            <label>Sexo
                                <select name="personas_sexo">
                                    <option value="">--</option>
                                    <option value="Masculino" <?= (($_POST['personas_sexo'] ?? $user['personas_sexo']) === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                    <option value="Femenino" <?= (($_POST['personas_sexo'] ?? $user['personas_sexo']) === 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                </select>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Datos de usuario</legend>
                        <div class="form-inline">
                            <label>Email
                                <input type="email" name="usuarios_email" value="<?= htmlspecialchars($_POST['usuarios_email'] ?? $user['usuarios_email']) ?>" required>
                            </label>

                            <?php if ($session_role === 'ADMINISTRADOR'): ?>
                                <label>Rol
                                    <select name="usuarios_rol" required>
                                        <option value="ADMINISTRADOR" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol']) === 'ADMINISTRADOR') ? 'selected' : '' ?>>ADMINISTRADOR</option>
                                        <option value="DIRECTOR" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol']) === 'DIRECTOR') ? 'selected' : '' ?>>DIRECTOR</option>
                                        <option value="DOCENTE" <?= (($_POST['usuarios_rol'] ?? $user['usuarios_rol']) === 'DOCENTE') ? 'selected' : '' ?>>DOCENTE</option>
                                    </select>
                                </label>
                            <?php else: ?>
                                <label>Rol (no editable)
                                    <input type="text" readonly value="<?= htmlspecialchars($user['usuarios_rol']) ?>">
                                    <small class="note">Sólo administradores pueden cambiar el rol.</small>
                                </label>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Institucional</legend>
                        <div class="form-inline">
                            <label>Tipo institucional
                                <select name="institucional_tipo" required>
                                    <?php $tipos = ['ALUMNO', 'DOCENTE', 'DIRECTOR', 'OTRO'];
                                    foreach ($tipos as $t): ?>
                                        <option value="<?= htmlspecialchars($t) ?>" <?= (($_POST['institucional_tipo'] ?? $inst_tipo) === $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>Escuela
                                <select name="escuelas_id" id="escuelas_id" required>
                                    <option value="">-- Seleccione escuela --</option>
                                    <?php foreach ($escuelas as $e): ?>
                                        <option value="<?= $e['escuelas_id'] ?>" <?= (($_POST['escuelas_id'] ?? $inst_esc) == $e['escuelas_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['escuelas_nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>Formación Profesional
                                <select name="formaciones_id" id="formaciones_id" required>
                                    <option value="">-- Seleccione formación --</option>
                                </select>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Cambiar contraseña (opcional)</legend>
                        <div class="form-inline">
                            <label>Contraseña actual
                                <input type="password" name="pass_actual" placeholder="Si no cambia, dejalo vacío">
                            </label>
                            <label>Nueva contraseña
                                <input type="password" name="pass_nueva" placeholder="Mínimo 6 caracteres">
                            </label>
                            <label>Confirmar nueva
                                <input type="password" name="pass_conf" placeholder="">
                            </label>
                        </div>
                    </fieldset>

                    <!-- SECCION DOMICILIOS: listado + formulario añadir -->
                    <fieldset>
                        <legend>Domicilios</legend>

                        <!-- Lista -->
                        <?php if (mysqli_num_rows($doms) === 0): ?>
                            <p>No tenés domicilios cargados.</p>
                        <?php else: ?>
                            <table class="table-small">
                                <thead><tr><th>Calle</th><th>Desc.</th><th>Pred.</th><th>Acción</th></tr></thead>
                                <tbody>
                                <?php
                                // re-fetch doms to iterate from start
                                $domsList = mysqli_query($conexion, "SELECT * FROM domicilios WHERE personas_id = {$personas_id} ORDER BY domicilios_predeterminado DESC, domicilios_id DESC");
                                while ($d = mysqli_fetch_assoc($domsList)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['domicilios_calle']) ?></td>
                                        <td><?= htmlspecialchars($d['domicilios_descripcion']) ?></td>
                                        <td><?= $d['domicilios_predeterminado'] ? 'Sí' : 'No' ?></td>
                                        <td>
                                            <!-- editar -> abre un pequeño form -->
                                            <details>
                                                <summary>Editar</summary>
                                                <form method="post" class="form-inline" style="margin-top:6px;">
                                                    <input type="hidden" name="action_type" value="edit_domicilio">
                                                    <input type="hidden" name="domicilios_id" value="<?= $d['domicilios_id'] ?>">
                                                    <label>Calle <input name="domicilios_calle" value="<?= htmlspecialchars($d['domicilios_calle']) ?>"></label>
                                                    <label>Desc <input name="domicilios_descripcion" value="<?= htmlspecialchars($d['domicilios_descripcion']) ?>"></label>
                                                    <label>Lat <input name="domicilios_latitud" value="<?= htmlspecialchars($d['domicilios_latitud']) ?>"></label>
                                                    <label>Lon <input name="domicilios_longitud" value="<?= htmlspecialchars($d['domicilios_longitud']) ?>"></label>
                                                    <label><input type="checkbox" name="domicilios_predeterminado" <?= $d['domicilios_predeterminado'] ? 'checked' : '' ?>> Predeterminado</label>
                                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                                </form>
                                                <form method="post" style="margin-top:6px;">
                                                    <input type="hidden" name="action_type" value="delete_domicilio">
                                                    <input type="hidden" name="domicilios_id" value="<?= $d['domicilios_id'] ?>">
                                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Confirmar eliminar domicilio?')">Eliminar</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- Form añadir -->
                        <details>
                            <summary>Agregar domicilio</summary>
                            <form method="post" style="margin-top:8px;">
                                <input type="hidden" name="action_type" value="add_domicilio">
                                <div class="form-inline">
                                    <label>Calle <input name="domicilios_calle" required></label>
                                    <label>Desc <input name="domicilios_descripcion"></label>
                                    <label>Lat <input name="domicilios_latitud"></label>
                                    <label>Lon <input name="domicilios_longitud"></label>
                                    <label><input type="checkbox" name="domicilios_predeterminado"> Predeterminado</label>
                                    <button type="submit" class="btn btn-primary">Agregar</button>
                                </div>
                            </form>
                        </details>
                    </fieldset>

                    <!-- SECCION TELEFONOS -->
                    <fieldset>
                        <legend>Teléfonos</legend>

                        <?php
                        $telsList = mysqli_query($conexion, "SELECT * FROM telefonos WHERE personas_id = {$personas_id} ORDER BY telefonos_predeterminado DESC, telefonos_id DESC");
                        if (mysqli_num_rows($telsList) === 0): ?>
                            <p>No tenés teléfonos cargados.</p>
                        <?php else: ?>
                            <table class="table-small">
                                <thead><tr><th>Teléfono</th><th>Desc.</th><th>Pred.</th><th>Acción</th></tr></thead>
                                <tbody>
                                <?php while ($t = mysqli_fetch_assoc($telsList)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['telefonos_numero']) ?></td>
                                        <td><?= htmlspecialchars($t['telefonos_descripcion']) ?></td>
                                        <td><?= $t['telefonos_predeterminado'] ? 'Sí' : 'No' ?></td>
                                        <td>
                                            <details>
                                                <summary>Editar</summary>
                                                <form method="post" class="form-inline" style="margin-top:6px;">
                                                    <input type="hidden" name="action_type" value="edit_telefono">
                                                    <input type="hidden" name="telefonos_id" value="<?= $t['telefonos_id'] ?>">
                                                    <label>Tel <input name="telefonos_numero" value="<?= htmlspecialchars($t['telefonos_numero']) ?>"></label>
                                                    <label>Desc <input name="telefonos_descripcion" value="<?= htmlspecialchars($t['telefonos_descripcion']) ?>"></label>
                                                    <label><input type="checkbox" name="telefonos_predeterminado" <?= $t['telefonos_predeterminado'] ? 'checked' : '' ?>> Predeterminado</label>
                                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                                </form>
                                                <form method="post" style="margin-top:6px;">
                                                    <input type="hidden" name="action_type" value="delete_telefono">
                                                    <input type="hidden" name="telefonos_id" value="<?= $t['telefonos_id'] ?>">
                                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Confirmar eliminar teléfono?')">Eliminar</button>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <details>
                            <summary>Agregar teléfono</summary>
                            <form method="post" style="margin-top:8px;">
                                <input type="hidden" name="action_type" value="add_telefono">
                                <div class="form-inline">
                                    <label>Teléfono <input name="telefonos_numero" required></label>
                                    <label>Desc <input name="telefonos_descripcion"></label>
                                    <label><input type="checkbox" name="telefonos_predeterminado"> Predeterminado</label>
                                    <button type="submit" class="btn btn-primary">Agregar</button>
                                </div>
                            </form>
                        </details>
                    </fieldset>
                </form>
            </section>
        <?php endif; ?>

    </main>

    <?php include('footer.php'); ?>

    <script>
    // Toast helper
    function showToast(message, type = 'success', timeout = 4000) {
        const container = document.getElementById('toast-container');
        const t = document.createElement('div');
        t.className = 'toast ' + (type === 'error' ? 'error' : 'success');
        t.innerHTML = message;
        container.appendChild(t);
        // animate in
        setTimeout(()=>t.classList.add('show'), 10);
        // remove later
        setTimeout(()=>{
            t.classList.remove('show');
            setTimeout(()=>container.removeChild(t), 300);
        }, timeout);
    }

    // On load, show flash messages passed from PHP (if any)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($flash_success)): ?>
            showToast(<?= json_encode($flash_success) ?>, 'success', 4500);
        <?php endif; ?>
        <?php if (!empty($flash_errors)): ?>
            <?php foreach ($flash_errors as $f): ?>
                showToast(<?= json_encode($f) ?>, 'error', 6000);
            <?php endforeach; ?>
        <?php endif; ?>
    });
    </script>

    <script>
    // JS para cargar formaciones según escuela (usa endpoint de registros.php)
    document.addEventListener('DOMContentLoaded', function() {
        const escSelect = document.getElementById('escuelas_id');
        const formSelect = document.getElementById('formaciones_id');
        const preSelectedForm = <?= json_encode($inst_form) ?>;
        const preSelectedEsc = <?= json_encode($inst_esc) ?>;

        function loadFormaciones(escuelaId, preselect) {
            if (!formSelect) return;
            formSelect.innerHTML = '<option value="">Cargando…</option>';
            fetch(`registros.php?endpoint=formaciones&escuela=${encodeURIComponent(escuelaId)}`)
                .then(r => r.json())
                .then(data => {
                    let h = '<option value="">-- Seleccione formación --</option>';
                    data.forEach(f => {
                        h += `<option value="${f.id}" ${f.id == preselect ? 'selected' : ''}>${f.nombre}</option>`;
                    });
                    formSelect.innerHTML = h;
                })
                .catch(err => {
                    formSelect.innerHTML = '<option value="">Error cargando</option>';
                    console.error(err);
                });
        }

        if (preSelectedEsc) loadFormaciones(preSelectedEsc, preSelectedForm);
        if (escSelect) {
            escSelect.addEventListener('change', function() {
                if (this.value) loadFormaciones(this.value, 0);
                else if (formSelect) formSelect.innerHTML = '<option value="">-- Seleccione formación --</option>';
            });
        }
    });
    </script>
</body>
</html>
