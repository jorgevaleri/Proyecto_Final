<?php
// deslogeo.php - destruir sesión de forma correcta y evitar cache
// -> NO debe haber salida (HTML/espacios) antes de las cabeceras

session_start();

// Limpiar todas las variables de sesión
$_SESSION = [];

// Si se usan cookies para la sesión, borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión en el servidor
session_destroy();

// CABECERAS para evitar que el navegador muestre páginas cacheadas al usar atrás/adelante
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Redirigir al login/index (usar 303 para POST->GET safe)
header('Location: index.php', true, 303);
exit;
