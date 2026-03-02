<?php
session_start();
$_SESSION = array();

//limpieza de cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
    );
}

//destruye info
session_destroy();

header("Location: login.php?mensaje=sesion_cerrada");
exit;
?>