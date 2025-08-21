<?php
// logeo.php - login con soporte de contraseñas en claro + rehaseo automático

if (session_status() === PHP_SESSION_NONE) session_start();
include('../BackEnd/conexion.php'); // ajustá la ruta si hace falta

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ingresar'])) {
    $usuarios_email = trim($_POST['usuarios_email'] ?? '');
    $usuarios_clave = $_POST['usuarios_clave'] ?? '';

    if ($usuarios_email === '' || $usuarios_clave === '') {
        $login_error = 'Complete email y contraseña.';
    } else {
        // Buscamos el usuario (solo no eliminados)
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
            $login_error = "El e-mail {$usuarios_email} no existe.";
            mysqli_stmt_close($stmt);
        } else {
            mysqli_stmt_bind_result($stmt, $uid, $hash_db, $email_db, $rol_db, $p_nombre, $p_apellido);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            $login_ok = false;

            // Detectar si la contraseña en DB parece un hash (bcrypt/argon)
            $is_hashed = (strpos($hash_db, '$2y$') === 0) || (strpos($hash_db, '$2a$') === 0) || (stripos($hash_db, 'argon') !== false);

            if ($is_hashed) {
                // Comparación normal con password_verify
                if (password_verify($usuarios_clave, $hash_db)) $login_ok = true;
            } else {
                // La contraseña está en claro en la DB -> comparamos directo
                if ($usuarios_clave === $hash_db) {
                    $login_ok = true;
                    // Re-hash y actualizar la DB para mejorar seguridad
                    $new_hash = password_hash($usuarios_clave, PASSWORD_DEFAULT);
                    $upd = mysqli_prepare($conexion, "UPDATE usuarios SET usuarios_clave = ? WHERE usuarios_id = ?");
                    mysqli_stmt_bind_param($upd, "si", $new_hash, $uid);
                    mysqli_stmt_execute($upd);
                    mysqli_stmt_close($upd);
                }
            }

            if ($login_ok) {
                // Función local para normalizar el rol de la BD a nuestros códigos
                function normalize_role($r)
                {
                    $r = strtoupper(trim((string)$r));
                    if (strpos($r, 'ADMIN') !== false || strpos($r, 'ADMINISTRADOR') !== false) return 'ADMIN';
                    if (strpos($r, 'DIRECTOR') !== false) return 'DIRECTOR';
                    // cubrir variantes: DOCENTE, PROFESOR, TEACHER...
                    if (strpos($r, 'DOCENT') !== false || strpos($r, 'PROFESOR') !== false || strpos($r, 'TEACHER') !== false) return 'DOCENTE';
                    // valor por defecto (evita null)
                    return 'DOCENTE';
                }

                // Guardamos datos en sesión (manteniendo lo que ya tenías)
                $_SESSION['user_id'] = (int)$uid;
                $_SESSION['usuarios_email'] = $email_db;
                $_SESSION['personas_nombre'] = $p_nombre;
                $_SESSION['personas_apellido'] = $p_apellido;

                // guardamos BOTH: el valor tal cual (para mostrar) y el role estandarizado (para checks)
                $_SESSION['personas_rol'] = $rol_db ?? 'DOCENTE';                  // valor original (para header)
                $_SESSION['role'] = normalize_role($_SESSION['personas_rol']);     // valor estándar (ADMIN/DIRECTOR/DOCENTE)

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
<?php include('head.php'); ?>

<!-- ESTILOS -->
<link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_public.css">
<script>
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.href = 'deslogeo.php';
            window.location.reload(true);
        }
    });
</script>
<?php include('header.php'); ?>

<body class="body">
    <main class="cuerpo">
        <div class="content">
            <?php if ($login_error): ?>
                <p class="error" style="color:red; text-align:center;"><?= htmlspecialchars($login_error) ?></p>
            <?php endif; ?>

            <form id="form1" name="form1" method="post">
                <h3 class="title">Iniciar Sesion</h3>

                <div class="field">
                    <input name="usuarios_email" type="text" id="usuarios_email" required
                        value="<?= htmlspecialchars($_POST['usuarios_email'] ?? '') ?>">
                    <span class="fas fa-user"></span>
                    <label for="usuarios_email">Correo Electronico</label>
                    <small class="error" id="error_email"></small>
                </div>

                <div class="field password-field">
                    <input type="password" name="usuarios_clave" id="usuarios_clave" required>
                    <i id="togglePassword" class="fas fa-eye-slash"></i>
                    <span class="fas fa-lock"></span>
                    <label for="usuarios_clave">Contraseña</label>
                    <small class="error" id="error_clave"></small>
                </div>

                <br>

                <div class="boton">
                    <input type="submit" name="ingresar" id="ingresar" value="Ingresar">
                </div>

                <div class="sign-up">
                    <input type="checkbox" id="rememberMe">
                    <label for="rememberMe">Recordarme</label>
                </div>

                <div class="forgot-pass">
                    <a href="olvide-contraseña.php">Olvide la contraseña</a>
                </div>
            </form>
        </div>
    </main>

    <script src="../BackEnd/logeo.js"></script>
</body>
<?php include('footer.php'); ?>

</html>