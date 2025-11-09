<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// MENSAJE DE ERRORES
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingresar'])) {
    $usuarios_email = trim($_POST['usuarios_email'] ?? '');
    $usuarios_clave = $_POST['usuarios_clave'] ?? '';

    // VALIDACION BASICA
    if ($usuarios_email === '' || $usuarios_clave === '') {
        $login_error = 'Complete email y contraseña.';
    } else {

        // BUSCAR USUARIO
        $stmt = mysqli_prepare($conexion, "
            SELECT u.usuarios_id, u.usuarios_clave, u.usuarios_email, u.usuarios_rol,
                   p.personas_nombre, p.personas_apellido
            FROM usuarios u
            JOIN personas p ON u.personas_id = p.personas_id
            WHERE u.usuarios_email = ? AND u.usuarios_eliminado = 0
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "s", $usuarios_email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 0) {

            // CORREO NO ENCONTRADO
            $login_error = "El e-mail " . htmlspecialchars($usuarios_email) . " no existe.";
            mysqli_stmt_close($stmt);
        } else {

            // RECUPERAR DATOS
            mysqli_stmt_bind_result($stmt, $uid, $hash_db, $email_db, $rol_db, $p_nombre, $p_apellido);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            $login_ok = false;

            // DETECTAR SI LA CONTRASEÑA EN LA BASE DE DATOS ESTA HASHEADA
            $is_hashed = (strpos($hash_db, '$2y$') === 0) || (strpos($hash_db, '$2a$') === 0) || (stripos($hash_db, 'argon') !== false);

            if ($is_hashed) {

                // COMPARAR CONTRASEÑA
                if (password_verify($usuarios_clave, $hash_db)) $login_ok = true;
            } else {

                if ($usuarios_clave === $hash_db) {
                    $login_ok = true;
                    $new_hash = password_hash($usuarios_clave, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conexion, "UPDATE usuarios SET usuarios_clave = ? WHERE usuarios_id = ?");
                    mysqli_stmt_bind_param($upd, "si", $new_hash, $uid);
                    mysqli_stmt_execute($upd);
                    mysqli_stmt_close($upd);
                }
            }

            if ($login_ok) {

                // GUARDA LOS DATOS EN SESION
                $_SESSION['user_id'] = (int)$uid;
                $_SESSION['usuarios_email'] = $email_db;
                $_SESSION['personas_nombre'] = $p_nombre;
                $_SESSION['personas_apellido'] = $p_apellido;
                $_SESSION['personas_rol'] = $rol_db ?? 'DOCENTE';
                $_SESSION['role'] = normalize_role($_SESSION['personas_rol']) ?? 'DOCENTE';

                // REDIRIGIR A LA PAGINA PRIVADA
                header('Location: menu_principal.php');
                exit;
            } else {
                $login_error = 'Contraseña incorrecta.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<!-- HEAD -->
<?php include('head.php'); ?>

<!-- ESTILOS CSS -->
<link rel="stylesheet" href="CSS/estilo_comun.css">
<link rel="stylesheet" href="CSS/estilo_publico.css">

<!-- HEADER -->
<?php include('header.php'); ?>

<body class="body login-page">
    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA -->
    <main class="cuerpo">
        <div class="content">
            <form id="form1" name="form1" method="post" novalidate>

                <!-- FORMULARIO DE INICIO DE SESIÓN -->
                <h3 class="title">Iniciar Sesión</h3>

                <!-- INPUT EMAIL -->
                <div class="field">
                    <input name="usuarios_email" type="text" id="usuarios_email" required
                        value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
                    <span class="fas fa-user" aria-hidden="true"></span>
                    <label for="usuarios_email">Correo Electrónico</label>
                </div>

                <!-- INPUT CONTRASEÑA -->
                <div class="field password-field">
                    <input type="password" name="usuarios_clave" id="usuarios_clave" required>
                    <i id="togglePassword" class="fas fa-eye-slash" aria-hidden="true"></i>
                    <span class="fas fa-lock" aria-hidden="true"></span>
                    <label for="usuarios_clave">Contraseña</label>
                </div>

                <!-- CONTENEDOR CENTRALIZADO PARA MENSAJES DE VALIDACIONES -->
                <div id="login_validation_message" role="status" aria-live="polite">
                    <?php if (!empty($login_error)): ?>
                        <div class="server-error"><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>
                </div>

                <!-- BOTONES / LINK -->
                <div class="boton">
                    <input type="submit" name="ingresar" id="ingresar" value="Ingresar">
                </div>

                <div class="sign-up">
                    <input type="checkbox" id="rememberMe">
                    <label for="rememberMe">Recordarme</label>
                </div>

                <div class="forgot-pass">
                    <a href="olvide_contrasenia.php">Olvidé la contraseña</a>
                </div>
            </form>
        </div>
    </main>

    <!-- SCRIPTS -->
    <!-- VALIDACIONES -->
    <script src="JS/validaciones_globales.js" defer></script>
    <!-- FUNCIONES PARA LOGEARSE -->
    <script src="JS/logeo.js" defer></script>
</body>

<!-- FOOTER -->
<?php include('footer.php'); ?>

</html>