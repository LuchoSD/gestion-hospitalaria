<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array((int)$_SESSION['rol'], [1, 3])){
    header("Location: ../login.php?error=no_autorizado");
    exit;
}
if($_SERVER ['REQUEST_METHOD'] == 'POST'){
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password_plana = $_POST['password'];
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $id_rol = (int)$_POST['id_rol'];
    $id_especialidad = ($id_rol == 2) ? $_POST ['id_especialidad'] :null;

    if (!$email || empty($password_plana) || empty($nombre)){
        $ruta = ($_SESSION['rol'] == 1) ? "../vistas/dashboard_admin.php" : "../vistas/r_pacientes.php";
        header("Location: $ruta?error=datos_incompletos");
        exit;
    }
    //comprobacion de email existente
    $c_email = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $c_email->execute([$email]);
    if($c_email->fetch()){
        $ruta = ($_SESSION['rol'] == 1) ? "../vistas/dashboard_admin.php" : "../vistas/r_pacientes.php";
        header("Location: $ruta?error=email_duplicado");
        exit;
    }
    //encriptacion de contrasena
    $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);
    
    try{
        $pdo->beginTransaction();

        $sql_u = "INSERT INTO usuarios (email, password, id_rol, cambio_pass, estado) VALUES (?, ?, ?, 1, 'activo')";
        $stmt_u = $pdo->prepare($sql_u);
        $stmt_u->execute([$email, $password_hash, $id_rol]);

        //id nuevo usuario
        $id_nuevo_usuario = $pdo->lastInsertId();

        //variable se guarda segun rol 4 paciente, otros empleado
        if($id_rol == 4){
            $sql_pac = "INSERT INTO pacientes (id_usuario, nombre, apellidos, dni, email, telefono, estado)
                        VALUES (?, ?, ?, ?, ?, ?, 'activo')";
            $stmt_pac = $pdo->prepare($sql_pac);
            $stmt_pac->execute([$id_nuevo_usuario, $nombre, $apellidos, $dni, $email, $telefono]);
            $redirect = "../vistas/r_pacientes.php?mensaje=paciente_creado";
        }else{
            $sql_p = "INSERT INTO perfiles_empleados (id_usuario, nombre, apellidos, id_especialidad, telefono) VALUES (?, ?, ?, ?, ?)";
            $stmt_p = $pdo->prepare($sql_p);
            $stmt_p->execute([$id_nuevo_usuario, $nombre, $apellidos, $id_especialidad, $telefono]);
            $redirect = "../vistas/empleado.php?mensaje=empleado_creado";
        }
        
        $pdo->commit();
        header("Location: $redirect");
    }catch(Exception $e){
        $pdo->rollBack();
        error_log("Error al registrar: ". $e->getMessage());
        $ruta = ($_SESSION['rol'] == 1) ? "../vistas/dashboard_admin.php" : "../vistas/r_pacientes.php";
        header("Location: $ruta?error=error_db");
    }
}
?>