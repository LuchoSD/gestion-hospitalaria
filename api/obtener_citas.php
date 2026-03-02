<?php
session_start();
require_once '../config/db.php';

header ('Content-type: application/json');

//filtro
if(!isset($_SESSION['user_id']) || (int)$_SESSION['rol'] != 3){
    echo json_encode(['error' =>'Acceso denegado']);
    exit;
}

$id_medico = $_GET['id_medico'] ?? '';
$sql = "SELECT c.id, c.fecha_cita, c.hora_cita, c.motivo, c.estado, p.nombre AS paciente_nom, pe.nombre AS medico_nom
        FROM citas c
        INNER JOIN pacientes p ON c.id_paciente = p.id
        INNER JOIN usuarios u on c.id_medico = u.id
        INNER JOIN perfiles_empleados pe ON u.id = pe.id_usuario
        WHERE c.estado != 'cancelada'";

if(!empty($id_medico) && is_numeric($id_medico)){
    $sql .= " AND c.id_medico = :id_medico";
}

$stmt = $pdo->prepare($sql);

if (!empty($id_medico) && is_numeric($id_medico)){
    $stmt->execute(['id_medico' => $id_medico]);
}else{
    $stmt->execute();
}

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$eventos = [];

foreach($resultados as $row){
    $color = ($row['estado'] == 'pendiente') ? '#f59e0b' : '#10b981';
    $eventos[] = [
        'id'    => $row['id'],
        'title' => $row['paciente_nom'] . " (Dr/a: " . $row['medico_nom'] . ")",
        'start' => $row['fecha_cita'] .  'T' .$row['hora_cita'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'motivo' => $row['motivo']
        ],
    ];
}
echo json_encode($eventos);