<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    //evito timing ttack con un hash generico
    $falso_hash = '$2y$10$123456789012345678901uQeWc5G8yqZr3xYp9tFvB2HkLmNoPq';

    //valdacion
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND estado = 'activo'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    //revision de bloqueo
    if($user){
        if (!is_null($user['ultimo_fallo']) && $user['intentos_fallidos'] >=5){
        $espera = (time() - strtotime($user['ultimo_fallo']))/60;
            if($espera < 15){
                header("Location: ../login.php?error=bloqueado");
                exit;
            }
        }
    }
    // validacion de credencals
    $hash_verif = $user ? $user['password'] : $falso_hash;
    $ok_pass = password_verify($password, $hash_verif);

    if ($user && $ok_pass) {
        $stmt_r = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = 0, ultimo_fallo = NULL WHERE id =?");
        $stmt_r->execute([$user['id']]);

        //medida para evitar Session Hijacking
        session_regenerate_id(true);

        //guardr datos de sesion
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['rol'] = $user['id_rol'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['ultimo_acceso'] = time();

        if((int)$user['cambio_pass'] === 1){
            header("Location: ../vistas/primer_login.php");
            exit;
        }else{
            //redirijo segun el rol
            switch ((int)$_SESSION['rol']){
                case 1: //admin
                    header("Location: ../vistas/dashboard_admin.php");
                    break;
                case 2: //medico
                    header("Location: ../vistas/dashboard_medico.php");
                    break;
                case 3: //recepcionista
                    header("Location: ../vistas/dashboard_recepcionista.php");
                    break;
                case 4: //paciente
                    header("Location: ../vistas/dashboard_paciente.php");
                    break;
                default:
                    session_unset();
                    session_destroy();
                    header("Location: ../login.php?error=rol_invalido");
                exit;
            }
        }
        exit;
    }else{
        if($user){
            $stmt_fallo = $pdo->prepare("UPDATE usuarios SET intentos_fallidos = intentos_fallidos + 1, ultimo_fallo = NOW() WHERE id = ?");
            $stmt_fallo->execute([$user['id']]);
        }else{
            password_verify($password, $falso_hash);
        }
        header("Location: ../login.php?error=credenciales_incorrectas");
        exit;
    }
}
?>