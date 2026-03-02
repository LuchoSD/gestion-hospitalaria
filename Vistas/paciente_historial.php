<?php 
session_start();
require_once '../config/db.php';
//filtros
if(!isset($_SESSION['user_id']) || $_SESSION['rol'] != 2){
    exit("Acceso denegado");
}

$id_paciente = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$id_medico = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmt->execute([$id_paciente]);
$paciente = $stmt->fetch();

$stmtCitas = $pdo->prepare("SELECT * FROM citas 
                            WHERE id_paciente = ? 
                            AND id_medico = ? 
                            ORDER BY fecha_cita DESC");
$stmtCitas->execute([$id_paciente, $id_medico]);
$citas = $stmtCitas->fetchAll();

if(!$paciente){
    exit("<p class='p-4 text-red-500'>Paciente no encontrado.</p>");
}
?>
<!--consultas y tabla-->
<div class="space-y-8">
    <div class="grid grid-cols-3 gap-4 bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div>
            <span class="block text-[10px] font-black text-slate-400 uppercase">Nombre Completo</span>
            <p class="font-bold text-slate-800"><?= htmlspecialchars($paciente['nombre'] . " " . $paciente['apellidos']) ?></p>
        </div>
        <div>
            <span class="block text-[10px] font-black text-slate-400 uppercase">DNI</span>
            <p class="font-bold text-slate-800"><?= htmlspecialchars($paciente['dni']) ?></p>
        </div>
        <div>
            <span class="block text-[10px] font-black text-slate-400 uppercase">Contacto</span>
            <p class="font-bold text-slate-800"><?= htmlspecialchars($paciente['telefono']) ?></p>
        </div>
    </div>
    <div>
        <h4 class ="text-blue-900 font-black uppercase text-xs mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-600 rounded-full"></span>Historial de Consultas Dr/a: <?= $_SESSION['email'] ?>
        </h4>

        <div class="space-y-4">
            <?php if(empty($citas)): ?>
                <p class="text-sm text-gray-500 italic text-center py-4">No hay consultas registradas.</p>
            <?php else: ?>
                <?php foreach($citas as $cita): ?>
                    <div class="bg-white p-4 rounded-2xl border-l-4 border-blue-500 shadow-sm">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-blue-600"><?= date('d/m/Y', strtotime($cita['fecha_cita'])) ?> - <?= $cita['hora_cita'] ?></span>
                            <span class="text-[10px] bg-slate-100 px-2 py-1 rounded-lg font-black uppercase text-slate-500"><?= $cita['estado'] ?></span>
                        </div>
                        <p class="text-sm mt-2 text-slate-700"><strong>Motivo:</strong> <?= htmlspecialchars($cita['motivo']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>