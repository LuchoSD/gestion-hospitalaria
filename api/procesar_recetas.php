<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['rol'] != 2)){
    header("Location: ../login.php?error=no_autorizado");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_paciente = $_POST['id_paciente'];
    $contenido = trim($_POST['contenido']);
    $id_medico = $_SESSION['user_id'];

    if(empty($id_paciente) || empty($contenido)) {
        header("Location: ../vistas/dashboard_medico.php?error=sin_datos");
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO recetas (id_paciente, id_medico, contenido) VALUES (?, ?, ?)");
        $stmt->execute([$id_paciente, $id_medico, $contenido]);
        
        header("Location: ../vistas/dashboard_medico.php?mensaje=receta_enviada");
    } catch (PDOException $e) {
        header("Location: ../vistas/dashboard_medico.php?error=error_db");
    }
}