<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php?error=sin_sesion");
    exit;
}
//confirmar coincidencia pass
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $pass1 = $_POST['nueva_pass'];
    $pass2 = $_POST['confirmar_pass'];

    if($pass1 != $pass2) {
        header("Location: primer_login.php?error=contrasena_no_coincide");
        exit;
    }

    //Revalido seguridad pass
    $regex = "/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{12,}/";
    if (!preg_match($regex, $pass1)){
        header("Location: primer_login.php?error=contrasena_debil");
        exit;
    }

    //Hash
    $password_hash = password_hash($pass1, PASSWORD_DEFAULT);
    $user_id = $_SESSION['user_id'];

    $sql = "UPDATE usuarios SET password = ?, cambio_pass = 0 WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    //redirecciono segun rol
    if($stmt->execute([$password_hash, $user_id])) {
        $rol = (int)$_SESSION['rol'];
        $destinos = [
            1 => "../vistas/dashboard_admin.php",
            2 => "../vistas/dashboard_medico.php",
            3 => "../vistas/dashboard_recepcionista.php",
            4 => "../vistas/dashboard_paciente.php"
        ];
        
        if(array_key_exists($rol, $destinos)){
            header("Location: " . $destinos[$rol] . "?mensaje=password_actualizado");
            exit;
        }else{
            session_unset();
            session_destroy();
            header("Location: ../login.php?error=rol_invalido");
            exit;
        }
    }else{
        header("Location: primer_login.php?error=error_db");
    }
    exit;
}
?>