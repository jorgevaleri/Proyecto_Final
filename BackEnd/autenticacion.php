<?php
// PREVENIR ACCESO DIRECTO POR SI QUIEREN INGRESAR POR URL DIRECTA
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// MODO (DEV / PROD) VISUALIZACION DE ERRORES
$ENV_DEV = true;

if ($ENV_DEV) {
    // MOSTRAMOS TODOS LOS ERRORES
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // OCULTAMOS TODOS LOS ERRORES
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// CONFIGURACION DE COOKIE DE SESION Y START
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = session_get_cookie_params();

if (session_status() === PHP_SESSION_NONE) {
    // SOLO SETEAR PARAMETROS SI LA SESION NO ESTA ACTIVA
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path'     => $cookieParams['path'],
        'domain'   => $cookieParams['domain'],
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
} else {
    // LA SESIÓN YA ESTA ACTIVA, NO SE CAMBIA LOS PARAMETROS
}

// GARANTIZAR SESION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CONSTANTES DE ROLES, PARA EVITAR INCONSISTENCIAS
define('ROL_ADMIN', 'ADMIN');
define('ROL_DIRECTOR', 'DIRECTOR');
define('ROL_DOCENTE', 'DOCENTE');

// UTILIDADES Y FUNCIONES PRINCIPALES

// NORMALIZAR ROLES
function normalize_role($r)
{
    $r = strtoupper(trim((string)$r));
    if (strpos($r, 'ADMIN') !== false || strpos($r, 'ADMINISTRADOR') !== false) return ROL_ADMIN;
    if (strpos($r, 'DIRECTOR') !== false) return ROL_DIRECTOR;
    if (strpos($r, 'DOCENT') !== false || strpos($r, 'PROFESOR') !== false || strpos($r, 'TEACHER') !== false) return ROL_DOCENTE;
    return null;
}

// DEVUELVE EL ROL ACTUAL
function current_role()
{
    if (isset($_SESSION['role']) && $_SESSION['role'] !== '') {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['personas_rol']) && $_SESSION['personas_rol'] !== '') {
        return normalize_role($_SESSION['personas_rol']);
    }
    return null;
}

// PREDICADOS DE CONVENIENCIAS
function is_admin()
{
    return current_role() === ROL_ADMIN;
}
function is_director()
{
    return current_role() === ROL_DIRECTOR;
}
function is_docente()
{
    return current_role() === ROL_DOCENTE;
}

// VISTA DE ROL REQUERIDA
function require_role_view($roles = [])
{
    if (!in_array(current_role(), $roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "No tiene permisos para ver esta página.";
        exit;
    }
}

// ACCIÓN DE ROL REQUERIDA
function require_role_action($roles = [])
{
    if (!in_array(current_role(), $roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "No tiene permisos para realizar esta acción.";
        exit;
    }
}

// LOGUEARSE REQUERIDO
function require_login($loginUrl = '/login.php')
{
    if (current_role() === null) {
        header("Location: $loginUrl");
        exit;
    }
}

// CIERRA LA SESIÓN DE FORMA SEGURA
function logout_helper()
{
    // LIMPIAR VARIABLE DE SESIÓN
    $_SESSION = [];

    // BORRAR COOKIE DE SESIÓN SI ESTÁ EN USO
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // DESTRUIR LA SESION
    session_destroy();
}

// FLAGS / CONFIGURACION SIMPLE
if (!isset($_SESSION['_helpers_flags'])) $_SESSION['_helpers_flags'] = [];