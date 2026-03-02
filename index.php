<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

switch ((int)$_SESSION['rol']) {
    case 1:
        header("Location: vistas/dashboard_admin.php");
        break;
    case 2:
        header("Location: vistas/dashboard_medico.php");
        break;
    case 3:
        header("Location: vistas/dashboard_recepcionista.php");
        break;
    case 4:
        header("Location: vistas/dashboard_paciente.php");
        break;
    default:
        session_unset();
        session_destroy();
        header("Location: login.php?error=rol_invalido");
        break;
}
exit;