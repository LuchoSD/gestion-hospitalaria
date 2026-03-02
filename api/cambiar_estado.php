<?php
session_start();
require_once '../config/db.php';

//config para que solo admi y recepcionista puedan cambiar el estado
if(!isset($_SESSION['user_id']) || ((int)$_SESSION['rol'] !=1 && (int)$_SESSION['rol'] != 3)){
    header("Location: ../login.php?error=no_autorizado");
    exit;
}
IF($_SERVER['REQUEST_METHOD']==='POST'){
    $id = (int)$_POST['id'] ?? 0;
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';
    $tabla_solicitada= $_POST['tabla'] ?? '';

    $tablas_permitidas = ['usuarios', 'pacientes'];
    if(!in_array($tabla_solicitada, $tablas_permitidas)){
        header("Location: ../login.php?error=tabla_invalida");
        exit;
    }

    //el recepcionista no puede tocar la tabla de empleados
    if((int)$_SESSION['rol'] == 3 && $tabla_solicitada == 'usuarios'){
        header("Location: ../vistas/r_pacientes.php?error=sin_privilegios");
        exit;
    }

    //el administrador no se puede desactivar a si mismo
    if ($tabla_solicitada === 'usuarios' && $id == (int)$_SESSION['user_id']){
        header("Location: ../vistas/empleado.php?error=error_autocambio");
        exit;
    }
    try{
        $sql = "UPDATE $tabla_solicitada SET estado = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nuevo_estado, $id]);

        //redireccion segun perfil admin o recepcionista
        if((int)$_SESSION['rol'] == 3){
            $redireccion = '../vistas/r_pacientes.php';
        }else{
            $redireccion = ($tabla_solicitada === 'usuarios') ? '../vistas/empleado.php' : '../r_pacientes.php';
        }
        header("Location: $redireccion?mensaje=estado_actualizado");
        exit;
    }catch (PDOException $e){
        //redireccion si hay error depende si es a empleados o a pacientes
        error_log("Error en cambio_estado: " . $e->getMessage());
        $error_destino = ((int)$_SESSION['rol'] == 3) ? '../r_pacientes.php' : '../empleado.php';
        header("Location: $error_destino?error=error_db");
        exit;
    }
}else{
    //evito que alguien entre si no usa el metodo POST
    header("Location: ../login.php?error=no_autorizado");
    exit;
}
    
?>