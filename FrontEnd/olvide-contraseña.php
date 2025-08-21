<!DOCTYPE html>
<html lang="en">

<?php
// ovide-contraseña.php

// Incluye head.php, que ya arranca session_start() y crea $conexion.
// También incluye el header de tu plantilla.
include('head.php');
include('header.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendReset'])) {
    $email = trim($_POST['email']);

    // 1) Verifica existencia de usuario
    $stmt = $conexion->prepare("
      SELECT usuarios_email 
      FROM usuarios 
      WHERE usuarios_email = ?
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $error = "Ese correo no está registrado.";
    } else {
        // 2) Genera token seguro
        $token   = bin2hex(random_bytes(32));
        $created = date('Y-m-d H:i:s');

        // 3) Guarda o actualiza en password_resets
        $stmt = $conexion->prepare("
          REPLACE INTO password_resets (email, token, created_at)
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param('sss', $email, $token, $created);
        $stmt->execute();

        // 4) Envía correo de recuperación
        $resetLink = "https://tu-dominio.com/reset-password.php?"
                   . "email=" . urlencode($email)
                   . "&token=" . urlencode($token);

        $asunto  = "Recuperar tu contraseña";
        $mensaje = "Hola,\n\n"
                 . "Haz click en este enlace para reestablecer tu contraseña:\n\n"
                 . $resetLink
                 . "\n\nSi no solicitaste esto, ignora este correo.";
        $headers = "From: no-reply@tu-dominio.com\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $asunto, $mensaje, $headers);
        $success = "Te hemos enviado un correo con instrucciones.";
    }
}
?>

<!-- Estilos -->
 <link rel="stylesheet" href="CSS/style_common.css">
<link rel="stylesheet" href="CSS/style_public.css">

<main class="cuerpo">
  <div class="reset-container">
    <h3 class="title">Recuperar contraseña</h3>

    <form method="post" class="reset-form">
      <div class="field">
        <input
          type="email"
          name="email"
          id="reset_email"
          required
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
        >
        <label for="reset_email">Correo electrónico</label>
      </div>

      <div class="boton">
        <input type="submit" class="btn" name="sendReset" value="Enviar enlace">
      </div>

      <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="message"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
    </form>

    <div class="back-login">
      <a href="logeo.php" class="btn">&larr; Volver al inicio de sesión</a>
    </div>
  </div>
</main>

<?php include('footer.php'); ?>
