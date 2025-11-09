<?php
// INICIALIZACION CENTRAL
require_once __DIR__ . '/includes/inicializar.php';

// MENSAJES PARA MOSTRAR EN VISTA
$error = '';
$success = '';

// PROCESAMIENTO DEL FORMULARIO POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendReset'])) {
  
  // NORMALIZAMOS EMAIL
  $usuarios_email = trim($_POST['email'] ?? '');
  
  // VALIDACIONES
  if ($usuarios_email === '') {
    $error = 'Por favor ingrese su correo electrónico.';
  } elseif (!filter_var($usuarios_email, FILTER_VALIDATE_EMAIL)) {
    $error = 'El formato del correo es inválido.';
  } else {
    if (!isset($conexion) || !($conexion instanceof mysqli)) {
      error_log('[olvide_contrasenia] No existe $conexion mysqli válida en ' . __FILE__);
      $error = 'Error interno: conexión a la base de datos no disponible.';
    } else {
      $sql = "SELECT usuarios_email FROM usuarios WHERE usuarios_email = ? LIMIT 1";
      $stmt = mysqli_prepare($conexion, $sql);
      if (!$stmt) {
        error_log('[olvide_contrasenia] Error en mysqli_prepare (select usuarios): ' . mysqli_error($conexion));
        $error = 'Error interno al verificar el usuario.';
      } else {
        mysqli_stmt_bind_param($stmt, 's', $usuarios_email);
        if (!mysqli_stmt_execute($stmt)) {
          error_log('[olvide_contrasenia] Error en mysqli_stmt_execute (select usuarios): ' . mysqli_error($conexion));
          $error = 'Error interno al verificar el usuario.';
          mysqli_stmt_close($stmt);
        } else {
          mysqli_stmt_store_result($stmt);
          $num = mysqli_stmt_num_rows($stmt);
          mysqli_stmt_close($stmt);

          if ($num === 0) {
            $error = 'El e-mail ' . htmlspecialchars($usuarios_email) . ' no existe.';
          } else {
            $fecha_crea = date('Y-m-d H:i:s');
            $sqlIns = "INSERT INTO recuperar_contrasenia (usuarios_email, recuperar_contrasenia_fechcrea) VALUES (?, ?)";
            $stmtIns = mysqli_prepare($conexion, $sqlIns);
            if (!$stmtIns) {
              error_log('[olvide_contrasenia] Error preparando INSERT recuperar_contrasenia: ' . mysqli_error($conexion));
              $error = 'Error interno al procesar la solicitud.';
            } else {
              mysqli_stmt_bind_param($stmtIns, 'ss', $usuarios_email, $fecha_crea);
              if (!mysqli_stmt_execute($stmtIns)) {
                error_log('[olvide_contrasenia] Error ejecutando INSERT recuperar_contrasenia: ' . mysqli_error($conexion));
                $error = 'Error interno al guardar la solicitud.';
                mysqli_stmt_close($stmtIns);
              } else {
                mysqli_stmt_close($stmtIns);
                $success = 'Te hemos enviado un correo con instrucciones.';
              }
            }
          }
        }
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

<!-- CUERPO PRINCIPAL -->
<body class="body login-page">
  <main class="cuerpo">
    <div class="reset-container">

      <h3 class="title">Recuperar contraseña</h3>

      <!-- FORMULARIO -->
      <form method="post" class="reset-form" novalidate>
        <div class="field">
          <input type="email" name="email" id="reset_email" required placeholder="Correo electrónico" aria-label="Correo electrónico"
            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          <span class="fas fa-user" aria-hidden="true"></span>
        </div>

        <!-- BOTON -->
        <div class="boton">
          <input type="submit" class="btn" name="sendReset" value="Enviar enlace">
        </div>

        <!-- CONTENEDOR PARA MENSAJE DE ERROR -->
        <?php if ($error): ?>
          <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
          <div class="message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
      </form>

      <!-- BOTON VOLVER -->
      <div class="back-login">
        <a href="logeo.php" class="btn">&larr; Volver al inicio de sesión</a>
      </div>
    </div>
  </main>

  <!-- SCRIPTS -->
  <script src="JS/validaciones_globales.js" defer></script>

  <!-- FOOTER -->
  <?php include('footer.php'); ?>
  
</body>
</html>
