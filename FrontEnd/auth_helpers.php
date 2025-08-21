<?php
// auth_helpers.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Normaliza el rol (como en logeo.php)
 */
function normalize_role($r) {
    $r = strtoupper(trim((string)$r));
    if (strpos($r, 'ADMIN') !== false || strpos($r, 'ADMINISTRADOR') !== false) return 'ADMIN';
    if (strpos($r, 'DIRECTOR') !== false) return 'DIRECTOR';
    if (strpos($r, 'DOCENT') !== false || strpos($r, 'PROFESOR') !== false || strpos($r, 'TEACHER') !== false) return 'DOCENTE';
    return null;
}

/**
 * Devuelve el role actual (o null)
 * Primero busca $_SESSION['role'] (el estandar), si no existe intenta normalizar $_SESSION['personas_rol']
 */
function current_role() {
    if (isset($_SESSION['role']) && $_SESSION['role'] !== '') {
        return $_SESSION['role'];
    }
    if (isset($_SESSION['personas_rol']) && $_SESSION['personas_rol'] !== '') {
        return normalize_role($_SESSION['personas_rol']);
    }
    return null;
}

function is_admin() {
    return current_role() === 'ADMIN';
}

function is_director() {
    return current_role() === 'DIRECTOR';
}

function is_docente() {
    return current_role() === 'DOCENTE';
}

function require_role_view($roles = []) {
    if (!in_array(current_role(), $roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "No tiene permisos para ver esta página.";
        exit;
    }
}

function require_role_action($roles = []) {
    if (!in_array(current_role(), $roles)) {
        header("HTTP/1.1 403 Forbidden");
        echo "No tiene permisos para realizar esta acción.";
        exit;
    }
}
