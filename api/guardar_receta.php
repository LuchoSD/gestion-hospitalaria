<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

//filtro
if (!isset($_SESSION['user_id']) || (int)$_SESSION['rol'] !== 2) {
    echo json_encode(['success' => false, 'error' => 'Sesión no válida']);
    exit;
}

$id_paciente = $_POST['id_paciente'] ?? null;
$contenido = trim($_POST['contenido'] ?? '');
$id_medico = $_SESSION['user_id'];

//errror falta de datos
if (!$id_paciente || empty($contenido)) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO recetas (id_paciente, id_medico, contenido, fecha_creacion) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$id_paciente, $id_medico, $contenido]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error DB: ' . $e->getMessage()]);
}