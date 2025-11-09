<?php
// DESTRUIR SESION DE FORMA CORRECTA Y EVITAR CACHE
session_start();

// LIMPIAR TODAS LAS VARIABLES DE SESION
$_SESSION = [];

// SI SE USAN COOKIES PARA LA SESION, BORRAR
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

// DESTRUIR LA SESION EN EL SERVIDOR
session_destroy();

// CABECERAS PARA EVITAR QUE EL NAVEGADOR MUESTRE PAGINAS CACHEADAS AL USAR ATRAS / ADELANTE
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// REDIRIGIR AL LOGIN / INDEX
header('Location: index.php', true, 303);
exit;
