<?php 
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || ((int)$_SESSION['rol'] != 3)) {
    header("Location: ../login.php?error=no_autorizado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id_paciente = (int)$_POST['id_paciente'];
    $id_medico = (int)$_POST['id_medico'];
    $fecha = $_POST['fecha_cita'];
    $hora = $_POST['hora_cita'];
    $motivo = trim($_POST['motivo'] ?? '');

    //validacion de fecha
    $fecha_actual = date('Y-m-d');
    if ($fecha < $fecha_actual){
        header("Location: ../vistas/r_pacientes.php?error=fecha_pasada");
        exit;
    }

    //horario clinica
    $hora_int = (int)str_replace(':', '', $hora);
    if($hora_int < 800 || $hora_int > 2000){
        header("Location: ../vistas/r_pacientes.php?error=fuera_de_horario");
        exit;
    }
    
    try{
        //veriffico disponibilidad
        $verif = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE id_medico = ? AND fecha_cita = ? AND hora_cita = ? AND estado != 'cancelada'");
        $verif->execute([$id_medico, $fecha, $hora]);

        if($verif->fetchColumn() > 0){
            header("Location: ../vistas/r_pacientes.php?error=horario_ocupado");
            exit;
        }

        $sql = "INSERT INTO citas (id_paciente, id_medico, fecha_cita, hora_cita, motivo, estado)
                VALUES (?, ?, ?, ?, ?, 'pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_paciente, $id_medico, $fecha, $hora, $motivo]);

                header("Location: ../vistas/r_pacientes.php?mensaje=cita_agendada");
                exit;
    }catch (PDOException $e){
        error_log("Error al agendar cita: " . $e-> getMessage());
        header("Location: ../vistas/r_pacientes.php?error=error_db");
        exit;
    }
}else{
    header("Location: ../login.php?error=no_autorizado");
    exit;
}
?>